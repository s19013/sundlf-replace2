<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use stdClass;
use Throwable;

/**
 * 旧システム(my-wiki)のSQLダンプ(database/my-wiki.sql)を、legacy接続経由で読み込んで
 * 新システムのDBへ移行する、1回限りのデータ移行コマンド。
 *
 * 事前準備(legacy接続先のDBにダンプを投入しておく):
 *   docker compose exec mariadb mysql -uroot -proot -e \
 *     "CREATE DATABASE IF NOT EXISTS sundlf_legacy CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
 *   docker compose exec -T mariadb mysql -uroot -proot sundlf_legacy < backend/database/my-wiki.sql
 *
 * 実行前提:
 *   php artisan migrate:fresh (seedなし)直後の、usersテーブルが空の状態で実行すること。
 *   DatabaseSeederのテストユーザーを先に作ると、自動採番のid=1が旧データのid=1
 *   (実際のプロジェクトオーナー)と主キー衝突する。
 */
class ImportLegacyDataCommand extends Command
{
    protected $signature = 'legacy:import
        {--fresh : 投入前に対象テーブルを全削除してから実行する}
        {--dry-run : 実際にはINSERTせず、件数と重複検出結果のみ表示する}
        {--chunk=500 : legacy側読み込み・書き込みのchunkサイズ}';

    protected $description = '旧システム(my-wiki)のデータを新システムのDBへ移行する(1回限りの実行を想定)';

    /** @var list<string> truncate用。子から親の順 */
    private const TABLES_CHILD_TO_PARENT = [
        'book_mark_statuses',
        'tag_statuses',
        'article_statuses',
        'book_mark_tags',
        'article_tags',
        'book_marks',
        'tags',
        'articles',
        'users',
    ];

    /** dry-run用。中間テーブル => [親テーブルへの外部キー, 親テーブル, 親の表示名] */
    private const TAGGING_TABLES = [
        'article_tags' => ['article_id', 'articles', 'メモ'],
        'book_mark_tags' => ['book_mark_id', 'book_marks', 'ブックマーク'],
    ];

    public function handle(): int
    {
        $legacy = DB::connection('legacy');
        $chunk = (int) $this->option('chunk');

        if ($this->option('dry-run')) {
            $this->showDryRun($legacy);

            return self::SUCCESS;
        }

        if ($this->option('fresh')) {
            if (! $this->confirm('users/articles/tags/book_marks等の既存データを全削除します。よろしいですか?')) {
                return self::FAILURE;
            }
            $this->truncateTargetTables();
        } elseif (DB::table('users')->exists()) {
            $this->warn('users テーブルに既存データがあります。--fresh の使用を検討してください。');
            if (! $this->confirm('このまま続行しますか?')) {
                return self::FAILURE;
            }
        }

        try {
            DB::transaction(function () use ($legacy, $chunk): void {
                $this->importUsers($legacy, $chunk);
                $this->importArticles($legacy, $chunk);
                $this->importTags($legacy, $chunk);
                $this->importBookMarks($legacy, $chunk);
                $this->importArticleTags($legacy, $chunk);
                $this->importBookMarkTags($legacy, $chunk);
            });
        } catch (Throwable $e) {
            $this->error('移行中にエラーが発生したため、変更はすべてロールバックされました。');
            $this->error($e->getMessage());
            Log::error('legacy:import failed', ['exception' => $e]);

            return self::FAILURE;
        }

        $this->info('移行が完了しました。');

        return self::SUCCESS;
    }

    private function showDryRun(ConnectionInterface $legacy): void
    {
        $this->info('=== dry-run: 件数確認 ===');
        foreach (['users', 'articles', 'tags', 'book_marks', 'article_tags', 'book_mark_tags'] as $table) {
            $this->line(sprintf('%s: %d 件', $table, $legacy->table($table)->count()));
        }

        $this->info('=== dry-run: tags の論理削除行のスキップ ===');
        $deletedTagsCount = $legacy->table('tags')->whereNotNull('deleted_at')->count();
        $this->line(sprintf('tags: 論理削除済みの%d件を移植しません', $deletedTagsCount));

        $this->info('=== dry-run: has_tags 判定 / tag_id NULL行・論理削除済みタグ参照行・所有者が異なる行のスキップ ===');
        foreach (self::TAGGING_TABLES as $table => [$foreignKeyColumn, $parentTable, $parentLabel]) {
            $nullTagCount = $legacy->table($table)->whereNull('tag_id')->count();
            $deletedTagCount = $legacy->table($table)
                ->whereIn('tag_id', fn (Builder $query) => $query->select('id')->from('tags')->whereNotNull('deleted_at'))
                ->count();
            $otherOwnerCount = $this->taggingWithLiveTagQuery($legacy, $table, $foreignKeyColumn, $parentTable, '<>')->count();
            $this->line(sprintf('%s: tag_id NULLの%d件をスキップします', $table, $nullTagCount));
            $this->line(sprintf('%s: 論理削除済みタグを参照する%d件をスキップします', $table, $deletedTagCount));
            $this->line(sprintf('%s: タグと%sの所有者が異なる%d件をスキップします', $table, $parentLabel, $otherOwnerCount));
            $this->line(sprintf(
                'has_tags=true になる %s: %d件 / %d件',
                $parentTable,
                count($this->collectTaggedIds($legacy, $table, $foreignKeyColumn, $parentTable)),
                $legacy->table($parentTable)->count(),
            ));
        }

        $this->info('=== dry-run: tags の重複(user_id + name)検出 ===');
        $duplicates = $this->detectDuplicateTags($legacy);
        if ($duplicates->isEmpty()) {
            $this->line('重複なし');

            return;
        }

        foreach ($duplicates as $duplicate) {
            $this->warn(sprintf(
                'user_id=%s name="%s" : id=%s は "%s" にリネームされます',
                $duplicate['user_id'],
                $duplicate['name'],
                $duplicate['id'],
                $duplicate['renamed_name'],
            ));
        }
    }

    private function truncateTargetTables(): void
    {
        Schema::disableForeignKeyConstraints();
        foreach (self::TABLES_CHILD_TO_PARENT as $table) {
            DB::table($table)->truncate();
        }
        Schema::enableForeignKeyConstraints();
    }

    /**
     * 中間テーブル(article_tags/book_mark_tags)のうち、新システムへ移行する行を絞り込むクエリ。
     * has_tags判定(collectTaggedIds)と中間テーブルの移行で同じ条件を使い、両者を一致させる。
     *
     * 次の行は移行しない。
     * - tag_idがNULLの行: 旧システムでの「タグなし」表現で、articles/book_marksのhas_tagsへ意味を統合したため
     *   (新スキーマではtag_idがNOT NULLのため投入もできない)
     * - 論理削除済みタグ(移行対象外)を参照する行
     * - タグの所有者とエントリーの所有者が異なる行: タグ削除時のhas_tagsの更新(DeleteTagUsecase)は、
     *   タグの所有者のエントリーだけを対象にするため、移行すると削除後もhas_tagsがtrueのまま残る
     *
     * @param  'article_tags'|'book_mark_tags'  $table
     * @param  'article_id'|'book_mark_id'  $foreignKeyColumn
     * @param  'articles'|'book_marks'  $parentTable
     */
    private function migratableTaggingQuery(ConnectionInterface $legacy, string $table, string $foreignKeyColumn, string $parentTable): Builder
    {
        return $this->taggingWithLiveTagQuery($legacy, $table, $foreignKeyColumn, $parentTable, '=');
    }

    /**
     * 中間テーブルのうち、親レコードと論理削除されていないタグが存在し、両者の所有者(user_id)が
     * $ownerOperatorの関係にある行を絞り込むクエリ。tagsとの結合で、tag_idがNULLの行も除かれる。
     *
     * @param  'article_tags'|'book_mark_tags'  $table
     * @param  'article_id'|'book_mark_id'  $foreignKeyColumn
     * @param  'articles'|'book_marks'  $parentTable
     * @param  '='|'<>'  $ownerOperator
     */
    private function taggingWithLiveTagQuery(ConnectionInterface $legacy, string $table, string $foreignKeyColumn, string $parentTable, string $ownerOperator): Builder
    {
        return $legacy->table($table)
            ->whereExists(function (Builder $query) use ($table, $foreignKeyColumn, $parentTable, $ownerOperator): void {
                $query->selectRaw('1')
                    ->from($parentTable)
                    ->join('tags', 'tags.user_id', $ownerOperator, "{$parentTable}.user_id")
                    ->whereColumn("{$parentTable}.id", "{$table}.{$foreignKeyColumn}")
                    ->whereColumn('tags.id', "{$table}.tag_id")
                    ->whereNull('tags.deleted_at');
            });
    }

    /**
     * 移行する中間テーブルの行(migratableTaggingQuery)を持つ、親レコード(article_id/book_mark_id)のID集合を取得する。
     * has_tags判定のためimportArticles/importBookMarks実行前に集計しておくことで、行ごとにexists()相当の
     * クエリを都度発行せずO(1)判定できるようにする。
     *
     * @param  'article_tags'|'book_mark_tags'  $table
     * @param  'article_id'|'book_mark_id'  $foreignKeyColumn
     * @param  'articles'|'book_marks'  $parentTable
     * @return array<int|string, true>
     */
    private function collectTaggedIds(ConnectionInterface $legacy, string $table, string $foreignKeyColumn, string $parentTable): array
    {
        $ids = $this->migratableTaggingQuery($legacy, $table, $foreignKeyColumn, $parentTable)
            ->distinct()
            ->pluck($foreignKeyColumn);

        return array_fill_keys($ids->all(), true);
    }

    /**
     * 旧データの行からedited_at(NOT NULL)に入れる日時を決める。
     *
     * 旧システムのupdated_atは、ユーザーが最後に編集した日時として使われていた。
     * 旧スキーマではupdated_at・created_atともにNULLを許すため、updated_at、created_at、現在時刻の順に補う。
     */
    private function legacyEditedAt(stdClass $row): string
    {
        return $row->updated_at ?? $row->created_at ?? now()->toDateTimeString();
    }

    private function importUsers(ConnectionInterface $legacy, int $chunk): void
    {
        $this->info('users を移行しています...');
        $bar = $this->output->createProgressBar($legacy->table('users')->count());

        $legacy->table('users')
            ->select('id', 'name', 'email', 'email_verified_at', 'password', 'remember_token', 'created_at', 'updated_at', 'logined_at')
            ->orderBy('id')
            ->chunkById($chunk, function (Collection $rows) use ($bar): void {
                DB::table('users')->insert($rows->map(function (stdClass $row): array {
                    return [
                        'id' => $row->id,
                        'name' => $row->name,
                        'email' => $row->email,
                        'email_verified_at' => $row->email_verified_at,
                        // Eloquentのhashedキャストを経由しないため、旧bcryptハッシュをそのまま保持する
                        'password' => $row->password,
                        'remember_token' => $row->remember_token,
                        'created_at' => $row->created_at,
                        'updated_at' => $row->updated_at,
                        'logined_at' => $row->logined_at,
                    ];
                })->all());
                $bar->advance($rows->count());
            });

        $bar->finish();
        $this->newLine();
    }

    private function importArticles(ConnectionInterface $legacy, int $chunk): void
    {
        $this->info('articles を移行しています...');
        $bar = $this->output->createProgressBar($legacy->table('articles')->count());

        $articleIdsWithTags = $this->collectTaggedIds($legacy, 'article_tags', 'article_id', 'articles');

        $legacy->table('articles')
            ->select('id', 'user_id', 'count', 'title', 'body', 'deleted_at', 'created_at', 'updated_at')
            ->orderBy('id')
            ->chunkById($chunk, function (Collection $rows) use ($bar, $articleIdsWithTags): void {
                DB::table('articles')->insert($rows->map(function (stdClass $row) use ($articleIdsWithTags): array {
                    return [
                        'id' => $row->id,
                        'user_id' => $row->user_id,
                        'star' => 0, // 旧データに存在しない新規カラムのため固定値
                        'title' => $row->title,
                        'body' => $row->body,
                        'has_tags' => isset($articleIdsWithTags[$row->id]),
                        'deleted_at' => $row->deleted_at,
                        'created_at' => $row->created_at,
                        'updated_at' => $row->updated_at,
                        'edited_at' => $this->legacyEditedAt($row),
                    ];
                })->all());
                // 生クエリのためcreatedイベントが発火しない。status行もここで作成する
                DB::table('article_statuses')->insert($rows->map(function (stdClass $row): array {
                    return [
                        'article_id' => $row->id,
                        'count' => $row->count,
                        'created_at' => $row->created_at,
                    ];
                })->all());
                $bar->advance($rows->count());
            });

        $bar->finish();
        $this->newLine();
    }

    private function importBookMarks(ConnectionInterface $legacy, int $chunk): void
    {
        $this->info('book_marks を移行しています...');
        $bar = $this->output->createProgressBar($legacy->table('book_marks')->count());

        $bookMarkIdsWithTags = $this->collectTaggedIds($legacy, 'book_mark_tags', 'book_mark_id', 'book_marks');

        $legacy->table('book_marks')
            ->select('id', 'user_id', 'count', 'title', 'url', 'deleted_at', 'created_at', 'updated_at')
            ->orderBy('id')
            ->chunkById($chunk, function (Collection $rows) use ($bar, $bookMarkIdsWithTags): void {
                DB::table('book_marks')->insert($rows->map(function (stdClass $row) use ($bookMarkIdsWithTags): array {
                    return [
                        'id' => $row->id,
                        'user_id' => $row->user_id,
                        'star' => 0, // 旧データに存在しない新規カラムのため固定値
                        'title' => $row->title,
                        'url' => $row->url,
                        'has_tags' => isset($bookMarkIdsWithTags[$row->id]),
                        'deleted_at' => $row->deleted_at,
                        'created_at' => $row->created_at,
                        'updated_at' => $row->updated_at,
                        'edited_at' => $this->legacyEditedAt($row),
                    ];
                })->all());
                // 生クエリのためcreatedイベントが発火しない。status行もここで作成する
                DB::table('book_mark_statuses')->insert($rows->map(function (stdClass $row): array {
                    return [
                        'book_mark_id' => $row->id,
                        'count' => $row->count,
                        'created_at' => $row->created_at,
                    ];
                })->all());
                $bar->advance($rows->count());
            });

        $bar->finish();
        $this->newLine();
    }

    /**
     * 新スキーマの unique(['user_id', 'name']) に抵触する重複(同一 user_id + name の組)を検出する。
     * id は article_tags/book_mark_tags から参照されているため変更しない。
     *
     * 論理削除済み(deleted_atが非NULL)の行は移植対象外のため、ここでは有効な行のみを対象に判定する。
     * 2件目以降が見つかった場合は、データを失わないよう名前だけリネームして両方投入する。
     *
     * @return Collection<int, array{id: int|string, user_id: int|string, name: string, renamed_name: string}>
     */
    private function detectDuplicateTags(ConnectionInterface $legacy): Collection
    {
        $rows = $legacy->table('tags')
            ->select('id', 'name', 'user_id')
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->get();

        $seen = [];
        $duplicates = collect();

        /** @var stdClass $row */
        foreach ($rows as $row) {
            $key = $row->user_id.':'.$row->name;

            if (isset($seen[$key])) {
                $duplicates->push([
                    'id' => $row->id,
                    'user_id' => $row->user_id,
                    'name' => $row->name,
                    'renamed_name' => sprintf('%s(旧タグID:%s)', $row->name, $row->id),
                ]);

                continue;
            }

            $seen[$key] = true;
        }

        return $duplicates;
    }

    private function importTags(ConnectionInterface $legacy, int $chunk): void
    {
        $this->info('tags を移行しています...');

        $duplicates = $this->detectDuplicateTags($legacy)->keyBy('id');

        foreach ($duplicates as $id => $duplicate) {
            $this->warn(sprintf(
                '重複タグ名を検出しリネームしました: user_id=%s, id=%s, "%s" -> "%s"',
                $duplicate['user_id'],
                $id,
                $duplicate['name'],
                $duplicate['renamed_name'],
            ));
            Log::warning('legacy:import タグ名リネーム', [
                'id' => $id,
                'user_id' => $duplicate['user_id'],
                'name' => $duplicate['name'],
                'renamed_name' => $duplicate['renamed_name'],
            ]);
        }

        // **論理削除済み(`deleted_at`が非NULL)の場合**: `tags` へ移行しない。
        // **論理削除済みタグを参照する`article_tags`/`book_mark_tags`行の場合**: 中間テーブルへ移行しない。
        // **有効なタグ同士で重複する場合**: 後続行の名前を `"{元の名前}(旧タグID:{id})"` にリネームして移行する。
        $rows = $legacy->table('tags')
            ->select('id', 'name', 'user_id', 'count', 'created_at', 'updated_at')
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->get();

        $bar = $this->output->createProgressBar($rows->count());

        $mapped = $rows->map(function (stdClass $row) use ($duplicates): array {
            $duplicate = $duplicates->get($row->id);

            return [
                'id' => $row->id,
                'name' => $duplicate['renamed_name'] ?? $row->name,
                'user_id' => $row->user_id,
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
                'edited_at' => $this->legacyEditedAt($row),
            ];
        });
        $counts = $rows->pluck('count', 'id');

        foreach ($mapped->chunk($chunk) as $batch) {
            DB::table('tags')->insert($batch->values()->all());
            // 生クエリのためcreatedイベントが発火しない。status行もここで作成する
            DB::table('tag_statuses')->insert($batch->map(fn (array $tag): array => [
                'tag_id' => $tag['id'],
                'count' => $counts[$tag['id']],
                'created_at' => $tag['created_at'],
            ])->values()->all());
            $bar->advance($batch->count());
        }

        $bar->finish();
        $this->newLine();
    }

    private function importArticleTags(ConnectionInterface $legacy, int $chunk): void
    {
        $this->info('article_tags を移行しています...');

        $bar = $this->output->createProgressBar(
            $this->migratableTaggingQuery($legacy, 'article_tags', 'article_id', 'articles')->count()
        );

        // tag_idがNULLの行・論理削除済みタグを参照する行・所有者が異なる行は移行しない(migratableTaggingQuery参照)。
        // article_id/tag_idの複合ユニークキーのみで単一の主キーがないためchunkByIdが使えず、
        // orderByによるオフセット方式で読み込む(legacyは読み取り専用でこの間に行が変化しないため安全)
        $this->migratableTaggingQuery($legacy, 'article_tags', 'article_id', 'articles')
            ->select('article_id', 'tag_id', 'created_at', 'updated_at')
            ->orderBy('article_id')
            ->orderBy('tag_id')
            ->chunk($chunk, function (Collection $rows) use ($bar): void {
                DB::table('article_tags')->insert($rows->map(function (stdClass $row): array {
                    return [
                        'article_id' => $row->article_id,
                        'tag_id' => $row->tag_id,
                        'created_at' => $row->created_at,
                        'updated_at' => $row->updated_at,
                    ];
                })->all());
                $bar->advance($rows->count());
            });

        $bar->finish();
        $this->newLine();
    }

    private function importBookMarkTags(ConnectionInterface $legacy, int $chunk): void
    {
        $this->info('book_mark_tags を移行しています...');

        $bar = $this->output->createProgressBar(
            $this->migratableTaggingQuery($legacy, 'book_mark_tags', 'book_mark_id', 'book_marks')->count()
        );

        // 移行しない行と読み込み方式はimportArticleTagsと同様
        $this->migratableTaggingQuery($legacy, 'book_mark_tags', 'book_mark_id', 'book_marks')
            ->select('book_mark_id', 'tag_id', 'created_at', 'updated_at')
            ->orderBy('book_mark_id')
            ->orderBy('tag_id')
            ->chunk($chunk, function (Collection $rows) use ($bar): void {
                DB::table('book_mark_tags')->insert($rows->map(function (stdClass $row): array {
                    return [
                        'book_mark_id' => $row->book_mark_id,
                        'tag_id' => $row->tag_id,
                        'created_at' => $row->created_at,
                        'updated_at' => $row->updated_at,
                    ];
                })->all());
                $bar->advance($rows->count());
            });

        $bar->finish();
        $this->newLine();
    }
}
