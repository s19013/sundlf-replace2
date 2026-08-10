<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\ConnectionInterface;
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
        'book_mark_tags',
        'article_tags',
        'book_marks',
        'tags',
        'articles',
        'users',
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

        $this->info('=== dry-run: has_tags 判定 / tag_id NULL行・論理削除済みタグ参照行のスキップ ===');
        $deletedTagIds = array_keys($this->collectDeletedTagIds($legacy));
        $articleTagsNull = $legacy->table('article_tags')->whereNull('tag_id')->count();
        $bookMarkTagsNull = $legacy->table('book_mark_tags')->whereNull('tag_id')->count();
        $articleTagsDeletedTag = $legacy->table('article_tags')->whereNotNull('tag_id')->whereIn('tag_id', $deletedTagIds)->count();
        $bookMarkTagsDeletedTag = $legacy->table('book_mark_tags')->whereNotNull('tag_id')->whereIn('tag_id', $deletedTagIds)->count();
        $this->line(sprintf('article_tags: tag_id NULLの%d件をスキップします', $articleTagsNull));
        $this->line(sprintf('article_tags: 論理削除済みタグを参照する%d件をスキップします', $articleTagsDeletedTag));
        $this->line(sprintf('book_mark_tags: tag_id NULLの%d件をスキップします', $bookMarkTagsNull));
        $this->line(sprintf('book_mark_tags: 論理削除済みタグを参照する%d件をスキップします', $bookMarkTagsDeletedTag));
        $this->line(sprintf(
            'has_tags=true になる articles: %d件 / %d件',
            count($this->collectTaggedIds($legacy, 'article_tags', 'article_id')),
            $legacy->table('articles')->count(),
        ));
        $this->line(sprintf(
            'has_tags=true になる book_marks: %d件 / %d件',
            count($this->collectTaggedIds($legacy, 'book_mark_tags', 'book_mark_id')),
            $legacy->table('book_marks')->count(),
        ));

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
     * 中間テーブル(article_tags/book_mark_tags)から、tag_idが設定されている行を持つ
     * 親レコード(article_id/book_mark_id)のID集合を取得する。has_tags判定のため
     * importArticles/importBookMarks実行前に集計しておくことで、行ごとにexists()相当の
     * クエリを都度発行せずO(1)判定できるようにする。
     *
     * 論理削除済みタグ(移行対象外)への参照は実際にはarticle_tags/book_mark_tagsへ
     * 移行されないため、has_tags判定からも除外する。
     *
     * @param  'article_tags'|'book_mark_tags'  $table
     * @param  'article_id'|'book_mark_id'  $foreignKeyColumn
     * @return array<int|string, true>
     */
    private function collectTaggedIds(ConnectionInterface $legacy, string $table, string $foreignKeyColumn): array
    {
        $deletedTagIds = array_keys($this->collectDeletedTagIds($legacy));

        $ids = $legacy->table($table)
            ->whereNotNull('tag_id')
            ->whereNotIn('tag_id', $deletedTagIds)
            ->distinct()
            ->pluck($foreignKeyColumn);

        return array_fill_keys($ids->all(), true);
    }

    /**
     * 論理削除済み(deleted_atが非NULL)のtagのID集合を取得する。移植対象外のタグへの
     * article_tags/book_mark_tags参照を除外するために使用する。
     *
     * @return array<int|string, true>
     */
    private function collectDeletedTagIds(ConnectionInterface $legacy): array
    {
        $ids = $legacy->table('tags')->whereNotNull('deleted_at')->pluck('id');

        return array_fill_keys($ids->all(), true);
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

        $articleIdsWithTags = $this->collectTaggedIds($legacy, 'article_tags', 'article_id');

        $legacy->table('articles')
            ->select('id', 'user_id', 'count', 'title', 'body', 'deleted_at', 'created_at', 'updated_at')
            ->orderBy('id')
            ->chunkById($chunk, function (Collection $rows) use ($bar, $articleIdsWithTags): void {
                DB::table('articles')->insert($rows->map(function (stdClass $row) use ($articleIdsWithTags): array {
                    return [
                        'id' => $row->id,
                        'user_id' => $row->user_id,
                        'count' => $row->count,
                        'star' => 0, // 旧データに存在しない新規カラムのため固定値
                        'title' => $row->title,
                        'body' => $row->body,
                        'has_tags' => isset($articleIdsWithTags[$row->id]),
                        'deleted_at' => $row->deleted_at,
                        'created_at' => $row->created_at,
                        'updated_at' => $row->updated_at,
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

        $bookMarkIdsWithTags = $this->collectTaggedIds($legacy, 'book_mark_tags', 'book_mark_id');

        $legacy->table('book_marks')
            ->select('id', 'user_id', 'count', 'title', 'url', 'deleted_at', 'created_at', 'updated_at')
            ->orderBy('id')
            ->chunkById($chunk, function (Collection $rows) use ($bar, $bookMarkIdsWithTags): void {
                DB::table('book_marks')->insert($rows->map(function (stdClass $row) use ($bookMarkIdsWithTags): array {
                    return [
                        'id' => $row->id,
                        'user_id' => $row->user_id,
                        'count' => $row->count,
                        'star' => 0, // 旧データに存在しない新規カラムのため固定値
                        'title' => $row->title,
                        'url' => $row->url,
                        'has_tags' => isset($bookMarkIdsWithTags[$row->id]),
                        'deleted_at' => $row->deleted_at,
                        'created_at' => $row->created_at,
                        'updated_at' => $row->updated_at,
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

        // 論理削除済み(deleted_atが非NULL)の行は移植しない
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
                'count' => $row->count,
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
            ];
        });

        foreach ($mapped->chunk($chunk) as $batch) {
            DB::table('tags')->insert($batch->all());
            $bar->advance($batch->count());
        }

        $bar->finish();
        $this->newLine();
    }

    private function importArticleTags(ConnectionInterface $legacy, int $chunk): void
    {
        $this->info('article_tags を移行しています...');

        $deletedTagIds = array_keys($this->collectDeletedTagIds($legacy));

        $bar = $this->output->createProgressBar(
            $legacy->table('article_tags')->whereNotNull('tag_id')->whereNotIn('tag_id', $deletedTagIds)->count()
        );

        // tag_idがNULLの行(旧システムでの「タグなし」表現)はarticles.has_tagsへ意味を
        // 統合したため移行しない(新スキーマではtag_idがNOT NULLのため投入もできない)。
        // 論理削除済みタグ(移行対象外)を参照する行も同様に移行しない。
        // article_id/tag_idの複合ユニークキーのみで単一の主キーがないためchunkByIdが使えず、
        // orderByによるオフセット方式で読み込む(legacyは読み取り専用でこの間に行が変化しないため安全)
        $legacy->table('article_tags')
            ->select('article_id', 'tag_id', 'created_at', 'updated_at')
            ->whereNotNull('tag_id')
            ->whereNotIn('tag_id', $deletedTagIds)
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

        $deletedTagIds = array_keys($this->collectDeletedTagIds($legacy));

        $bar = $this->output->createProgressBar(
            $legacy->table('book_mark_tags')->whereNotNull('tag_id')->whereNotIn('tag_id', $deletedTagIds)->count()
        );

        // tag_idがNULLの行、および論理削除済みタグを参照する行はimportArticleTagsと同様の理由で移行しない
        $legacy->table('book_mark_tags')
            ->select('book_mark_id', 'tag_id', 'created_at', 'updated_at')
            ->whereNotNull('tag_id')
            ->whereNotIn('tag_id', $deletedTagIds)
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
