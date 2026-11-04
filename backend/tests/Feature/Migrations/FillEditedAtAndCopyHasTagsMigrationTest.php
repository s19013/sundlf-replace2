<?php

namespace Tests\Feature\Migrations;

use App\Models\BookMark;
use App\Models\BookMarkStatus;
use App\Models\Memo;
use App\Models\MemoStatus;
use App\Models\Tag;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FillEditedAtAndCopyHasTagsMigrationTest extends TestCase
{
    use RefreshDatabase;

    private function runMigration(string $method): void
    {
        $migration = require database_path('migrations/2026_11_04_093018_fill_edited_at_and_copy_has_tags_to_articles_book_marks_and_tags_tables.php');
        assert($migration instanceof Migration && method_exists($migration, $method));
        $migration->{$method}();
    }

    public function test_upでゴミ箱内も含めた全行のedited_atにupdated_atが写されること(): void
    {
        $memo = Memo::factory()->create();
        $trashedMemo = Memo::factory()->create();
        $trashedMemo->delete();
        $bookMark = BookMark::factory()->create();
        $trashedBookMark = BookMark::factory()->create();
        $trashedBookMark->delete();
        $tag = Tag::factory()->create();

        // 行の取り違えを検出できるよう、行ごとに異なる値を入れる
        DB::table('articles')->where('id', $memo->id)->update(['updated_at' => '2020-01-01 00:00:01']);
        DB::table('articles')->where('id', $trashedMemo->id)->update(['updated_at' => '2020-01-01 00:00:02']);
        DB::table('book_marks')->where('id', $bookMark->id)->update(['updated_at' => '2020-01-01 00:00:03']);
        DB::table('book_marks')->where('id', $trashedBookMark->id)->update(['updated_at' => '2020-01-01 00:00:04']);
        DB::table('tags')->where('id', $tag->id)->update(['updated_at' => '2020-01-01 00:00:05']);

        $this->runMigration('up');

        $this->assertDatabaseHas('articles', ['id' => $memo->id, 'edited_at' => '2020-01-01 00:00:01']);
        $this->assertDatabaseHas('articles', ['id' => $trashedMemo->id, 'edited_at' => '2020-01-01 00:00:02']);
        $this->assertDatabaseHas('book_marks', ['id' => $bookMark->id, 'edited_at' => '2020-01-01 00:00:03']);
        $this->assertDatabaseHas('book_marks', ['id' => $trashedBookMark->id, 'edited_at' => '2020-01-01 00:00:04']);
        $this->assertDatabaseHas('tags', ['id' => $tag->id, 'edited_at' => '2020-01-01 00:00:05']);
    }

    public function test_upでstatusのhas_tagsがゴミ箱内も含めて本体へ写されること(): void
    {
        $memo = Memo::factory()->create();
        $memoWithoutTags = Memo::factory()->create();
        $trashedMemo = Memo::factory()->create();
        $trashedMemo->delete();
        $bookMark = BookMark::factory()->create();
        $trashedBookMark = BookMark::factory()->create();
        $trashedBookMark->delete();

        MemoStatus::whereKey($memo->id)->update(['has_tags' => true]);
        MemoStatus::whereKey($trashedMemo->id)->update(['has_tags' => true]);
        BookMarkStatus::whereKey($bookMark->id)->update(['has_tags' => true]);
        BookMarkStatus::whereKey($trashedBookMark->id)->update(['has_tags' => true]);

        $this->runMigration('up');

        $this->assertDatabaseHas('articles', ['id' => $memo->id, 'has_tags' => true]);
        $this->assertDatabaseHas('articles', ['id' => $memoWithoutTags->id, 'has_tags' => false]);
        $this->assertDatabaseHas('articles', ['id' => $trashedMemo->id, 'has_tags' => true]);
        $this->assertDatabaseHas('book_marks', ['id' => $bookMark->id, 'has_tags' => true]);
        $this->assertDatabaseHas('book_marks', ['id' => $trashedBookMark->id, 'has_tags' => true]);
    }

    public function test_upでstatus行が無い本体のhas_tagsはfalseのままになること(): void
    {
        $memo = Memo::factory()->create();
        $bookMark = BookMark::factory()->create();
        DB::table('article_statuses')->delete();
        DB::table('book_mark_statuses')->delete();

        $this->runMigration('up');

        $this->assertDatabaseHas('articles', ['id' => $memo->id, 'has_tags' => false]);
        $this->assertDatabaseHas('book_marks', ['id' => $bookMark->id, 'has_tags' => false]);
    }

    public function test_upでupdated_atを変更しないこと(): void
    {
        $memo = Memo::factory()->create();
        $bookMark = BookMark::factory()->create();
        $tag = Tag::factory()->create();
        MemoStatus::whereKey($memo->id)->update(['has_tags' => true]);
        BookMarkStatus::whereKey($bookMark->id)->update(['has_tags' => true]);
        $memoUpdatedAt = DB::table('articles')->where('id', $memo->id)->value('updated_at');
        $bookMarkUpdatedAt = DB::table('book_marks')->where('id', $bookMark->id)->value('updated_at');
        $tagUpdatedAt = DB::table('tags')->where('id', $tag->id)->value('updated_at');

        $this->travel(1)->minutes();
        $this->runMigration('up');

        $this->assertSame($memoUpdatedAt, DB::table('articles')->where('id', $memo->id)->value('updated_at'));
        $this->assertSame($bookMarkUpdatedAt, DB::table('book_marks')->where('id', $bookMark->id)->value('updated_at'));
        $this->assertSame($tagUpdatedAt, DB::table('tags')->where('id', $tag->id)->value('updated_at'));
    }

    public function test_downで本体のhas_tagsがゴミ箱内も含めてstatusテーブルへ書き戻されること(): void
    {
        $memo = Memo::factory()->create();
        $memoWithoutTags = Memo::factory()->create();
        $trashedMemo = Memo::factory()->create();
        $trashedMemo->delete();
        $bookMark = BookMark::factory()->create();
        $bookMarkWithoutTags = BookMark::factory()->create();

        DB::table('articles')->whereIn('id', [$memo->id, $trashedMemo->id])->update(['has_tags' => true]);
        DB::table('book_marks')->where('id', $bookMark->id)->update(['has_tags' => true]);
        // 本体がfalseのときに、statusがfalseへ書き戻されることも確認する
        MemoStatus::whereKey($memoWithoutTags->id)->update(['has_tags' => true]);
        BookMarkStatus::whereKey($bookMarkWithoutTags->id)->update(['has_tags' => true]);

        $this->runMigration('down');

        $this->assertDatabaseHas('article_statuses', ['article_id' => $memo->id, 'has_tags' => true]);
        $this->assertDatabaseHas('article_statuses', ['article_id' => $memoWithoutTags->id, 'has_tags' => false]);
        $this->assertDatabaseHas('article_statuses', ['article_id' => $trashedMemo->id, 'has_tags' => true]);
        $this->assertDatabaseHas('book_mark_statuses', ['book_mark_id' => $bookMark->id, 'has_tags' => true]);
        $this->assertDatabaseHas('book_mark_statuses', ['book_mark_id' => $bookMarkWithoutTags->id, 'has_tags' => false]);
    }
}
