<?php

namespace Tests\Feature\Models;

use App\Models\BookMark;
use App\Models\Memo;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StatusRowTest extends TestCase
{
    use RefreshDatabase;

    public function test_メモを作成するとstatus行が作成されること(): void
    {
        $memo = Memo::factory()->create();

        $this->assertDatabaseHas('article_statuses', [
            'article_id' => $memo->id,
            'has_tags' => false,
            'count' => 0,
        ]);
    }

    public function test_ブックマークを作成するとstatus行が作成されること(): void
    {
        $bookMark = BookMark::factory()->create();

        $this->assertDatabaseHas('book_mark_statuses', [
            'book_mark_id' => $bookMark->id,
            'has_tags' => false,
            'count' => 0,
        ]);
    }

    public function test_タグを作成するとstatus行が作成されること(): void
    {
        $tag = Tag::factory()->create();

        $this->assertDatabaseHas('tag_statuses', [
            'tag_id' => $tag->id,
            'count' => 0,
        ]);
    }

    public function test_論理削除してもstatus行は残ること(): void
    {
        $memo = Memo::factory()->create();
        $bookMark = BookMark::factory()->create();

        $memo->delete();
        $bookMark->delete();

        $this->assertDatabaseHas('article_statuses', ['article_id' => $memo->id]);
        $this->assertDatabaseHas('book_mark_statuses', ['book_mark_id' => $bookMark->id]);
    }

    public function test_物理削除するとstatus行も削除されること(): void
    {
        $memo = Memo::factory()->create();
        $bookMark = BookMark::factory()->create();
        $tag = Tag::factory()->create();

        $memo->forceDelete();
        $bookMark->forceDelete();
        $tag->delete();

        $this->assertDatabaseMissing('article_statuses', ['article_id' => $memo->id]);
        $this->assertDatabaseMissing('book_mark_statuses', ['book_mark_id' => $bookMark->id]);
        $this->assertDatabaseMissing('tag_statuses', ['tag_id' => $tag->id]);
    }
}
