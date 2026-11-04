<?php

namespace Tests\Feature\Migrations;

use App\Models\BookMark;
use App\Models\BookMarkStatus;
use App\Models\Memo;
use App\Models\MemoStatus;
use App\Models\Tag;
use App\Models\TagStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CopyCountAndHasTagsToStatusTablesMigrationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * コピー元の本体の`count`・`has_tags`は後続のマイグレーションで削除されるため、後続のマイグレーションを新しい順に`down()`して復元してから検証する。
     * 本体へ`has_tags`を戻すマイグレーション(2026_11_04_092911以降)も先に取り消し、カラムの重複を避ける。
     * テストDB(SQLite)ではDDLもトランザクション内で実行されるので、テスト終了時に元のスキーマへ戻る。
     */
    protected function setUp(): void
    {
        parent::setUp();

        // 新しい順に取り消す
        $laterMigrationFileNames = [
            '2026_11_04_093018_fill_edited_at_and_copy_has_tags_to_articles_book_marks_and_tags_tables.php',
            '2026_11_04_092911_add_edited_at_and_has_tags_to_articles_book_marks_and_tags_tables.php',
            '2026_11_03_043655_drop_count_and_has_tags_from_articles_book_marks_and_tags_tables.php',
        ];

        foreach ($laterMigrationFileNames as $fileName) {
            $migration = require database_path("migrations/{$fileName}");
            assert($migration instanceof Migration && method_exists($migration, 'down'));
            $migration->down();
        }

        // 現在のモデルは作成時にedited_atを書き込むため、ファクトリでデータを作れるようedited_atだけを戻す
        foreach (['articles', 'book_marks', 'tags'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->timestamp('edited_at')->nullable();
            });
        }
    }

    private function runCopyMigration(string $method): void
    {
        $migration = require database_path('migrations/2026_11_03_042851_copy_count_and_has_tags_to_status_tables.php');
        assert($migration instanceof Migration && method_exists($migration, $method));
        $migration->{$method}();
    }

    /**
     * マイグレーション実行前の状態(status行が無く、値は本体にある)を作る。
     */
    private function deleteAllStatusRows(): void
    {
        DB::table('article_statuses')->delete();
        DB::table('book_mark_statuses')->delete();
        DB::table('tag_statuses')->delete();
    }

    public function test_upで本体の値を持つstatus行がゴミ箱内も含めて全行分作成されること(): void
    {
        $memo = Memo::factory()->create();
        $trashedMemo = Memo::factory()->create();
        $trashedMemo->delete();
        $bookMark = BookMark::factory()->create();
        $trashedBookMark = BookMark::factory()->create();
        $trashedBookMark->delete();
        $tag = Tag::factory()->create();
        $this->deleteAllStatusRows();

        // has_tagsとcountの取り違えを検出できるよう、行ごとに異なる値を入れる
        DB::table('articles')->where('id', $memo->id)->update(['count' => 3, 'has_tags' => true]);
        DB::table('articles')->where('id', $trashedMemo->id)->update(['count' => 5, 'has_tags' => false]);
        DB::table('book_marks')->where('id', $bookMark->id)->update(['count' => 7, 'has_tags' => true]);
        DB::table('book_marks')->where('id', $trashedBookMark->id)->update(['count' => 9, 'has_tags' => false]);
        DB::table('tags')->where('id', $tag->id)->update(['count' => 2]);

        $this->runCopyMigration('up');

        $this->assertDatabaseCount('article_statuses', 2);
        $this->assertDatabaseHas('article_statuses', ['article_id' => $memo->id, 'count' => 3, 'has_tags' => true]);
        $this->assertDatabaseHas('article_statuses', ['article_id' => $trashedMemo->id, 'count' => 5, 'has_tags' => false]);
        $this->assertDatabaseCount('book_mark_statuses', 2);
        $this->assertDatabaseHas('book_mark_statuses', ['book_mark_id' => $bookMark->id, 'count' => 7, 'has_tags' => true]);
        $this->assertDatabaseHas('book_mark_statuses', ['book_mark_id' => $trashedBookMark->id, 'count' => 9, 'has_tags' => false]);
        $this->assertDatabaseCount('tag_statuses', 1);
        $this->assertDatabaseHas('tag_statuses', ['tag_id' => $tag->id, 'count' => 2]);
    }

    public function test_upでstatus行のcreated_atに本体のcreated_atがコピーされること(): void
    {
        $memo = Memo::factory()->create(['created_at' => '2020-01-02 03:04:05']);
        $bookMark = BookMark::factory()->create(['created_at' => '2020-01-02 03:04:05']);
        $tag = Tag::factory()->create(['created_at' => '2020-01-02 03:04:05']);
        $this->deleteAllStatusRows();

        $this->runCopyMigration('up');

        $this->assertDatabaseHas('article_statuses', ['article_id' => $memo->id, 'created_at' => '2020-01-02 03:04:05']);
        $this->assertDatabaseHas('book_mark_statuses', ['book_mark_id' => $bookMark->id, 'created_at' => '2020-01-02 03:04:05']);
        $this->assertDatabaseHas('tag_statuses', ['tag_id' => $tag->id, 'created_at' => '2020-01-02 03:04:05']);
    }

    public function test_downでstatusの値が本体へ書き戻されstatus行が削除されること(): void
    {
        $memo = Memo::factory()->create();
        $trashedMemo = Memo::factory()->create();
        $trashedMemo->delete();
        $bookMark = BookMark::factory()->create();
        $tag = Tag::factory()->create();

        MemoStatus::whereKey($memo->id)->update(['count' => 3, 'has_tags' => true]);
        MemoStatus::whereKey($trashedMemo->id)->update(['count' => 5, 'has_tags' => false]);
        BookMarkStatus::whereKey($bookMark->id)->update(['count' => 7, 'has_tags' => true]);
        TagStatus::whereKey($tag->id)->update(['count' => 2]);

        $this->runCopyMigration('down');

        $this->assertDatabaseHas('articles', ['id' => $memo->id, 'count' => 3, 'has_tags' => true]);
        $this->assertDatabaseHas('articles', ['id' => $trashedMemo->id, 'count' => 5, 'has_tags' => false]);
        $this->assertDatabaseHas('book_marks', ['id' => $bookMark->id, 'count' => 7, 'has_tags' => true]);
        $this->assertDatabaseHas('tags', ['id' => $tag->id, 'count' => 2]);
        $this->assertDatabaseCount('article_statuses', 0);
        $this->assertDatabaseCount('book_mark_statuses', 0);
        $this->assertDatabaseCount('tag_statuses', 0);
    }

    public function test_downでstatus行が無い本体は既定値になること(): void
    {
        $memo = Memo::factory()->create();
        $bookMark = BookMark::factory()->create();
        $tag = Tag::factory()->create();
        DB::table('articles')->where('id', $memo->id)->update(['count' => 3, 'has_tags' => true]);
        DB::table('book_marks')->where('id', $bookMark->id)->update(['count' => 7, 'has_tags' => true]);
        DB::table('tags')->where('id', $tag->id)->update(['count' => 2]);
        $this->deleteAllStatusRows();

        $this->runCopyMigration('down');

        $this->assertDatabaseHas('articles', ['id' => $memo->id, 'count' => 0, 'has_tags' => false]);
        $this->assertDatabaseHas('book_marks', ['id' => $bookMark->id, 'count' => 0, 'has_tags' => false]);
        $this->assertDatabaseHas('tags', ['id' => $tag->id, 'count' => 0]);
    }
}
