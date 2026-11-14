<?php

namespace Tests\Feature\Migrations;

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * edited_atの新設とhas_tagsの本体への戻し(3段階のマイグレーション)の仕上げと、移行全体の往復を検証する。
 */
class FinalizeEditedAtAndHasTagsMigrationTest extends TestCase
{
    /**
     * RefreshDatabaseのトランザクションは使わない。
     * SQLiteではカラムの変更(change())でテーブルを作り直し、その間だけPRAGMAで外部キーを無効にするが、
     * トランザクション内ではPRAGMAが効かず、ON DELETE CASCADEで子テーブル(status・中間テーブル)の行が消えてしまうため。
     * テストDBはインメモリなので、テストごとに作り直される。
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('migrate:fresh');
    }

    /**
     * 今回の3段階のマイグレーション(古い順)。
     */
    private const MIGRATION_FILE_NAMES = [
        '2026_11_04_092911_add_edited_at_and_has_tags_to_articles_book_marks_and_tags_tables.php',
        '2026_11_04_093018_fill_edited_at_and_copy_has_tags_to_articles_book_marks_and_tags_tables.php',
        '2026_11_14_132716_finalize_edited_at_and_has_tags_on_articles_book_marks_and_tags_tables.php',
    ];

    private function runMigration(string $fileName, string $method): void
    {
        $migration = require database_path("migrations/{$fileName}");
        assert($migration instanceof Migration && method_exists($migration, $method));
        $migration->{$method}();
    }

    private function rollbackAll(): void
    {
        foreach (array_reverse(self::MIGRATION_FILE_NAMES) as $fileName) {
            $this->runMigration($fileName, 'down');
        }
    }

    private function migrateAll(): void
    {
        foreach (self::MIGRATION_FILE_NAMES as $fileName) {
            $this->runMigration($fileName, 'up');
        }
    }

    private function isNullable(string $tableName, string $columnName): bool
    {
        $column = collect(Schema::getColumns($tableName))->firstWhere('name', $columnName);
        if ($column === null) {
            $this->fail("{$tableName}.{$columnName} was not found.");
        }

        return (bool) $column['nullable'];
    }

    public function test_upでedited_atがnot_nullになること(): void
    {
        foreach (['articles', 'book_marks', 'tags'] as $tableName) {
            $this->assertFalse($this->isNullable($tableName, 'edited_at'), $tableName);
        }
    }

    public function test_upでedited_atの複合インデックスに作り直されること(): void
    {
        foreach (['articles', 'book_marks'] as $tableName) {
            $this->assertTrue(Schema::hasIndex($tableName, ['user_id', 'deleted_at', 'edited_at']), $tableName);
            $this->assertTrue(Schema::hasIndex($tableName, ['user_id', 'has_tags', 'deleted_at', 'edited_at']), $tableName);
            $this->assertFalse(Schema::hasIndex($tableName, ['user_id', 'deleted_at', 'updated_at']), $tableName);
        }
    }

    public function test_upでstatusテーブルからhas_tagsが削除されること(): void
    {
        $this->assertFalse(Schema::hasColumn('article_statuses', 'has_tags'));
        $this->assertFalse(Schema::hasColumn('book_mark_statuses', 'has_tags'));
    }

    public function test_downでstatusのhas_tags・旧インデックス・nullableのedited_atに戻ること(): void
    {
        $this->runMigration(self::MIGRATION_FILE_NAMES[2], 'down');

        $this->assertTrue(Schema::hasColumn('article_statuses', 'has_tags'));
        $this->assertTrue(Schema::hasColumn('book_mark_statuses', 'has_tags'));

        foreach (['articles', 'book_marks'] as $tableName) {
            $this->assertTrue(Schema::hasIndex($tableName, ['user_id', 'deleted_at', 'updated_at']), $tableName);
            $this->assertFalse(Schema::hasIndex($tableName, ['user_id', 'deleted_at', 'edited_at']), $tableName);
            $this->assertFalse(Schema::hasIndex($tableName, ['user_id', 'has_tags', 'deleted_at', 'edited_at']), $tableName);
        }

        foreach (['articles', 'book_marks', 'tags'] as $tableName) {
            $this->assertTrue($this->isNullable($tableName, 'edited_at'), $tableName);
        }
    }

    public function test_3段階を往復すると移行前の構造と値に戻ること(): void
    {
        $this->rollbackAll();
        $this->seedLegacyData();
        $before = $this->snapshot();

        $this->migrateAll();
        $this->rollbackAll();

        $this->assertEquals($before, $this->snapshot());
    }

    public function test_移行後も編集日時順の並び順とタグなし検索の結果が移行前と変わらないこと(): void
    {
        $this->rollbackAll();
        $userId = $this->seedLegacyData();

        // 移行前のアプリと同じ条件(updated_at降順・id降順、statusのhas_tagsがtrueの行が無い)で結果を求める
        $legacyQuery = fn (bool $isTrashed, bool $isTagNotAttached) => DB::table('articles')
            ->where('user_id', $userId)
            ->when($isTrashed, fn ($query) => $query->whereNotNull('deleted_at'), fn ($query) => $query->whereNull('deleted_at'))
            ->when($isTagNotAttached, fn ($query) => $query->whereNotExists(
                fn ($statusQuery) => $statusQuery->from('article_statuses')
                    ->whereColumn('article_statuses.article_id', 'articles.id')
                    ->where('article_statuses.has_tags', true)
            ))
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->pluck('id')
            ->all();

        $cases = [
            ['is_in_trashbox' => false, 'is_tag_not_attached' => false],
            ['is_in_trashbox' => false, 'is_tag_not_attached' => true],
            ['is_in_trashbox' => true, 'is_tag_not_attached' => false],
            ['is_in_trashbox' => true, 'is_tag_not_attached' => true],
        ];
        $expected = array_map(fn (array $case) => $legacyQuery($case['is_in_trashbox'], $case['is_tag_not_attached']), $cases);

        $this->migrateAll();

        $user = User::findOrFail($userId);
        foreach ($cases as $index => $case) {
            $response = $this->actingAs($user)->withHeader('Referer', 'http://localhost')->getJson('/api/memos/search?'.http_build_query([
                'item_number' => 100,
                'is_in_trashbox' => $case['is_in_trashbox'] ? '1' : '0',
                'is_tag_not_attached' => $case['is_tag_not_attached'] ? '1' : '0',
            ]));

            $response->assertStatus(200);
            $this->assertSame($expected[$index], $response->json('memos.*.id'), json_encode($case) ?: '');
        }
    }

    /**
     * 移行前の構造(edited_atが無く、has_tagsはstatusテーブル)に、移行の検証用データを入れる。
     * 現在のモデルは移行後の構造を前提にしているため、DB::table()で入れる。
     *
     * @return int メモを持つユーザーのid
     */
    private function seedLegacyData(): int
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $tagId = DB::table('tags')->insertGetId([
            'user_id' => $user->id, 'name' => 'タグ', 'created_at' => '2026-01-01 00:00:00', 'updated_at' => '2026-01-03 00:00:00',
        ]);
        DB::table('tag_statuses')->insert(['tag_id' => $tagId, 'count' => 2, 'created_at' => '2026-01-01 00:00:00']);

        // [user_id, updated_at, deleted_at, statusのhas_tags(nullはstatus行なし)]
        $articles = [
            [$user->id, '2026-02-01 00:00:00', null, true],
            [$user->id, '2026-02-03 00:00:00', null, false],
            [$user->id, '2026-02-02 00:00:00', null, null],
            // updated_atが同値の行は、id降順で並ぶ
            [$user->id, '2026-02-03 00:00:00', null, true],
            [$user->id, '2026-02-04 00:00:00', '2026-02-05 00:00:00', true],
            [$user->id, '2026-02-06 00:00:00', '2026-02-07 00:00:00', false],
            [$otherUser->id, '2026-02-08 00:00:00', null, false],
        ];

        foreach ($articles as $index => [$articleUserId, $updatedAt, $deletedAt, $hasTags]) {
            $articleId = DB::table('articles')->insertGetId([
                'user_id' => $articleUserId,
                'star' => $index % 6,
                'title' => "メモ{$index}",
                'body' => "本文{$index}",
                'created_at' => '2026-01-01 00:00:00',
                'updated_at' => $updatedAt,
                'deleted_at' => $deletedAt,
            ]);

            if ($hasTags !== null) {
                DB::table('article_statuses')->insert([
                    'article_id' => $articleId, 'has_tags' => $hasTags, 'count' => $index, 'created_at' => '2026-01-01 00:00:00',
                ]);
            }

            if ($hasTags === true) {
                DB::table('article_tags')->insert(['article_id' => $articleId, 'tag_id' => $tagId]);
            }
        }

        foreach ([[true, null], [false, '2026-02-09 00:00:00']] as $index => [$hasTags, $deletedAt]) {
            $bookMarkId = DB::table('book_marks')->insertGetId([
                'user_id' => $user->id,
                'star' => 0,
                'title' => "ブックマーク{$index}",
                'url' => "https://example.com/{$index}",
                'created_at' => '2026-01-01 00:00:00',
                'updated_at' => "2026-02-1{$index} 00:00:00",
                'deleted_at' => $deletedAt,
            ]);
            DB::table('book_mark_statuses')->insert([
                'book_mark_id' => $bookMarkId, 'has_tags' => $hasTags, 'count' => $index, 'created_at' => '2026-01-01 00:00:00',
            ]);
        }

        return $user->id;
    }

    /**
     * 移行で変わりうるテーブルの構造(カラム・インデックス)と全行の値。
     * SQLiteはカラムの位置を指定できないため、カラムは名前順で比べる。
     *
     * @return array<string, mixed>
     */
    private function snapshot(): array
    {
        $snapshot = [];

        foreach (['articles', 'book_marks', 'tags', 'article_statuses', 'book_mark_statuses', 'tag_statuses', 'article_tags'] as $tableName) {
            $snapshot[$tableName] = [
                'columns' => collect(Schema::getColumns($tableName))
                    ->map(fn (array $column) => [$column['name'], $column['type_name'], $column['nullable'], $column['default']])
                    ->sortBy(fn (array $column) => $column[0])
                    ->values()
                    ->all(),
                'indexes' => collect(Schema::getIndexes($tableName))
                    ->map(fn (array $index) => [$index['columns'], $index['unique'], $index['primary']])
                    ->sortBy(fn (array $index) => implode(',', $index[0]))
                    ->values()
                    ->all(),
                'rows' => DB::table($tableName)->get()->map(fn (object $row) => (array) $row)->sortBy(fn (array $row) => json_encode($row))->values()->all(),
            ];
        }

        return $snapshot;
    }
}
