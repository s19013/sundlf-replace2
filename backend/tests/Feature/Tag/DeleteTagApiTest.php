<?php

namespace Tests\Feature\Tag;

use App\Models\BookMark;
use App\Models\Memo;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class DeleteTagApiTest extends TestCase
{
    use RefreshDatabase;

    /** @return TestResponse<\Illuminate\Http\Response> */
    private function spaDelete(string $url): TestResponse
    {
        return $this->withHeader('Referer', 'http://localhost')
            ->deleteJson($url);
    }

    // --- 正常系 ---

    public function test_正常な削除で200が返ること(): void
    {
        $user = User::factory()->create();
        $tag = Tag::factory()->create(['user_id' => $user->id, 'name' => '削除対象タグ']);

        $response = $this->actingAs($user)->spaDelete("/api/tags/{$tag->id}");

        $response->assertStatus(200);
        $response->assertJson(['messages' => ['削除対象タグを削除しました。']]);
    }

    public function test_削除するとtagsテーブルから物理削除されること(): void
    {
        $user = User::factory()->create();
        $tag = Tag::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)->spaDelete("/api/tags/{$tag->id}");

        $this->assertDatabaseMissing('tags', ['id' => $tag->id]);
    }

    public function test_削除すると中間テーブルの紐付けも削除されること(): void
    {
        $user = User::factory()->create();
        $tag = Tag::factory()->create(['user_id' => $user->id]);
        $memo = Memo::factory()->create(['user_id' => $user->id]);
        $bookMark = BookMark::factory()->create(['user_id' => $user->id]);
        $memo->tags()->attach($tag->id);
        $bookMark->tags()->attach($tag->id);

        $this->actingAs($user)->spaDelete("/api/tags/{$tag->id}");

        $this->assertDatabaseMissing('article_tags', ['tag_id' => $tag->id]);
        $this->assertDatabaseMissing('book_mark_tags', ['tag_id' => $tag->id]);
    }

    public function test_唯一のタグを削除するとメモとブックマークのhas_tagsがfalseになること(): void
    {
        $user = User::factory()->create();
        $tag = Tag::factory()->create(['user_id' => $user->id]);
        $memo = Memo::factory()->create(['user_id' => $user->id]);
        $bookMark = BookMark::factory()->create(['user_id' => $user->id]);
        $memo->tags()->attach($tag->id);
        $bookMark->tags()->attach($tag->id);

        $this->actingAs($user)->spaDelete("/api/tags/{$tag->id}");

        $this->assertDatabaseHas('articles', ['id' => $memo->id, 'has_tags' => false]);
        $this->assertDatabaseHas('book_marks', ['id' => $bookMark->id, 'has_tags' => false]);
    }

    public function test_他のタグが残っているメモのhas_tagsはtrueのままであること(): void
    {
        $user = User::factory()->create();
        $tag = Tag::factory()->create(['user_id' => $user->id]);
        $remainingTag = Tag::factory()->create(['user_id' => $user->id]);
        $memo = Memo::factory()->create(['user_id' => $user->id]);
        $memo->tags()->attach([$tag->id, $remainingTag->id]);

        $this->actingAs($user)->spaDelete("/api/tags/{$tag->id}");

        $this->assertDatabaseHas('articles', ['id' => $memo->id, 'has_tags' => true]);
    }

    public function test_ゴミ箱内のメモもhas_tagsがfalseになること(): void
    {
        $user = User::factory()->create();
        $tag = Tag::factory()->create(['user_id' => $user->id]);
        $memo = Memo::factory()->create(['user_id' => $user->id]);
        $memo->tags()->attach($tag->id);
        $memo->delete();

        $this->actingAs($user)->spaDelete("/api/tags/{$tag->id}");

        $this->assertDatabaseHas('articles', ['id' => $memo->id, 'has_tags' => false]);
    }

    public function test_ゴミ箱内のブックマークもhas_tagsがfalseになること(): void
    {
        $user = User::factory()->create();
        $tag = Tag::factory()->create(['user_id' => $user->id]);
        $bookMark = BookMark::factory()->create(['user_id' => $user->id]);
        $bookMark->tags()->attach($tag->id);
        $bookMark->delete();

        $this->actingAs($user)->spaDelete("/api/tags/{$tag->id}");

        $this->assertDatabaseHas('book_marks', ['id' => $bookMark->id, 'has_tags' => false]);
    }

    public function test_タグを削除するとメモのupdated_atは更新されedited_atは変わらないこと(): void
    {
        $user = User::factory()->create();
        $tag = Tag::factory()->create(['user_id' => $user->id]);
        $memo = Memo::factory()->create(['user_id' => $user->id]);
        $memo->tags()->attach($tag->id);
        $before = $memo->fresh();

        $this->travel(1)->minutes();

        $this->actingAs($user)->spaDelete("/api/tags/{$tag->id}");

        $fresh = $memo->fresh();
        $this->assertNotSame($before?->updated_at?->toIso8601String(), $fresh?->updated_at?->toIso8601String());
        $this->assertSame($before?->edited_at?->toIso8601String(), $fresh?->edited_at?->toIso8601String());
    }

    public function test_タグを削除した後にメモを更新しても競合にならないこと(): void
    {
        $user = User::factory()->create();
        $tag = Tag::factory()->create(['user_id' => $user->id]);
        $memo = Memo::factory()->create(['user_id' => $user->id, 'title' => '旧タイトル']);
        $memo->tags()->attach($tag->id);

        $this->travel(1)->minutes();
        $fetchedAt = now()->toIso8601String();
        $this->travel(1)->minutes();

        $this->actingAs($user)->spaDelete("/api/tags/{$tag->id}")->assertStatus(200);

        $response = $this->withHeader('Referer', 'http://localhost')->patchJson("/api/memos/{$memo->id}", [
            'title' => '新タイトル',
            'fetched_at' => $fetchedAt,
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('articles', ['id' => $memo->id, 'title' => '新タイトル']);
    }

    public function test_タグを削除しても編集日時順の並びが変わらないこと(): void
    {
        $user = User::factory()->create();
        $tag = Tag::factory()->create(['user_id' => $user->id]);
        $olderMemo = Memo::factory()->create(['user_id' => $user->id, 'edited_at' => '2026-01-01 00:00:00']);
        $newerMemo = Memo::factory()->create(['user_id' => $user->id, 'edited_at' => '2026-01-02 00:00:00']);
        $olderMemo->tags()->attach($tag->id);

        $this->actingAs($user)->spaDelete("/api/tags/{$tag->id}")->assertStatus(200);

        $response = $this->withHeader('Referer', 'http://localhost')->getJson('/api/memos/search');

        $response->assertStatus(200);
        $this->assertSame([$newerMemo->id, $olderMemo->id], $response->json('memos.*.id'));
    }

    public function test_タグ名を変更してもメモのedited_atは変わらないこと(): void
    {
        $user = User::factory()->create();
        $tag = Tag::factory()->create(['user_id' => $user->id, 'name' => '旧タグ']);
        $memo = Memo::factory()->create(['user_id' => $user->id]);
        $memo->tags()->attach($tag->id);
        $beforeEditedAt = $memo->fresh()?->edited_at?->toIso8601String();

        $this->travel(1)->minutes();

        $this->actingAs($user)->withHeader('Referer', 'http://localhost')
            ->patchJson("/api/tags/{$tag->id}", ['name' => '新タグ'])
            ->assertStatus(200);

        $this->assertSame($beforeEditedAt, $memo->fresh()?->edited_at?->toIso8601String());
    }

    // --- 異常系 ---

    public function test_削除対象のタグが存在しない場合404が返ること(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->spaDelete('/api/tags/9999');

        $response->assertStatus(404);
        $response->assertJson(['messages' => ['タグが見つかりませんでした。']]);
    }

    public function test_他人のタグを削除しようとすると404が返ること(): void
    {
        $owner = User::factory()->create();
        $tag = Tag::factory()->create(['user_id' => $owner->id]);
        $otherUser = User::factory()->create();

        $response = $this->actingAs($otherUser)->spaDelete("/api/tags/{$tag->id}");

        $response->assertStatus(404);
        $response->assertJson(['messages' => ['タグが見つかりませんでした。']]);
        $this->assertDatabaseHas('tags', ['id' => $tag->id]);
    }

    public function test_idが数値でない場合422が返ること(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->spaDelete('/api/tags/abc');

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('id');
    }

    public function test_未認証の場合401が返ること(): void
    {
        $tag = Tag::factory()->create();

        $response = $this->spaDelete("/api/tags/{$tag->id}");

        $response->assertStatus(401);
    }
}
