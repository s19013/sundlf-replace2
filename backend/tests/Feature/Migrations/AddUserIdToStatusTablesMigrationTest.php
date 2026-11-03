<?php

namespace Tests\Feature\Migrations;

use App\Models\BookMark;
use App\Models\Memo;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * statusテーブルに`user_id`を追加するマイグレーション(カラム追加・値のコピー・NOT NULL化とインデックス作成)のテスト。
 * テストDB(SQLite)ではDDLもトランザクション内で実行されるので、テスト終了時に元のスキーマへ戻る。
 */
class AddUserIdToStatusTablesMigrationTest extends TestCase
{
    use RefreshDatabase;

    private const ADD_COLUMN = '2026_11_03_064738_add_user_id_to_article_statuses_and_book_mark_statuses_tables';

    private const COPY = '2026_11_03_064808_copy_user_id_to_article_statuses_and_book_mark_statuses_tables';

    private const ADD_INDEX = '2026_11_03_064809_add_user_id_index_to_article_statuses_and_book_mark_statuses_tables';

    private function runMigration(string $name, string $method): void
    {
        $migration = require database_path("migrations/{$name}.php");
        assert($migration instanceof Migration && method_exists($migration, $method));
        $migration->{$method}();
    }

    public function test_コピーのupで本体のuser_idがゴミ箱内も含めて全行にコピーされること(): void
    {
        $memo = Memo::factory()->create();
        $otherUsersMemo = Memo::factory()->create();
        $trashedMemo = Memo::factory()->create();
        $trashedMemo->delete();
        $bookMark = BookMark::factory()->create();
        $trashedBookMark = BookMark::factory()->create();
        $trashedBookMark->delete();

        // コピー前の状態(user_idが空)を作る
        $this->runMigration(self::ADD_INDEX, 'down');
        DB::table('article_statuses')->update(['user_id' => null]);
        DB::table('book_mark_statuses')->update(['user_id' => null]);

        $this->runMigration(self::COPY, 'up');

        foreach ([$memo, $otherUsersMemo, $trashedMemo] as $entry) {
            $this->assertDatabaseHas('article_statuses', ['article_id' => $entry->id, 'user_id' => $entry->user_id]);
        }
        foreach ([$bookMark, $trashedBookMark] as $entry) {
            $this->assertDatabaseHas('book_mark_statuses', ['book_mark_id' => $entry->id, 'user_id' => $entry->user_id]);
        }
    }

    public function test_インデックスのdownとupで複合インデックスが削除と作成されること(): void
    {
        $this->runMigration(self::ADD_INDEX, 'down');

        $this->assertFalse(Schema::hasIndex('article_statuses', ['user_id', 'has_tags']));
        $this->assertFalse(Schema::hasIndex('book_mark_statuses', ['user_id', 'has_tags']));

        $this->runMigration(self::ADD_INDEX, 'up');

        $this->assertTrue(Schema::hasIndex('article_statuses', ['user_id', 'has_tags']));
        $this->assertTrue(Schema::hasIndex('book_mark_statuses', ['user_id', 'has_tags']));
    }

    public function test_全てのdownでuser_idが削除されること(): void
    {
        foreach ([self::ADD_INDEX, self::COPY, self::ADD_COLUMN] as $name) {
            $this->runMigration($name, 'down');
        }

        $this->assertFalse(Schema::hasColumn('article_statuses', 'user_id'));
        $this->assertFalse(Schema::hasColumn('book_mark_statuses', 'user_id'));
    }
}
