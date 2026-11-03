<?php

namespace Tests\Feature\Models;

use App\Models\BookMark;
use App\Models\Memo;
use App\Models\Tag;
use App\Models\User;
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
            'user_id' => $memo->user_id,
            'has_tags' => false,
            'count' => 0,
        ]);
    }

    public function test_ブックマークを作成するとstatus行が作成されること(): void
    {
        $bookMark = BookMark::factory()->create();

        $this->assertDatabaseHas('book_mark_statuses', [
            'book_mark_id' => $bookMark->id,
            'user_id' => $bookMark->user_id,
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

    public function test_所有者を変更するとstatus行のuser_idも変更されること(): void
    {
        $memo = Memo::factory()->create();
        $bookMark = BookMark::factory()->create();
        $newOwner = User::factory()->create();

        $memo->update(['user_id' => $newOwner->id]);
        $bookMark->update(['user_id' => $newOwner->id]);

        $this->assertDatabaseHas('article_statuses', ['article_id' => $memo->id, 'user_id' => $newOwner->id]);
        $this->assertDatabaseHas('book_mark_statuses', ['book_mark_id' => $bookMark->id, 'user_id' => $newOwner->id]);
    }

    public function test_所有者以外を変更してもstatus行は変更されないこと(): void
    {
        $memo = Memo::factory()->create();
        // 所有者以外の更新でstatus行を書き換えていないことを検出するため、status行の値を本体からずらしておく
        $otherUser = User::factory()->create();
        $memo->status()->update(['user_id' => $otherUser->id]);

        $memo->update(['title' => '変更後のタイトル']);

        $this->assertDatabaseHas('article_statuses', ['article_id' => $memo->id, 'user_id' => $otherUser->id]);
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
