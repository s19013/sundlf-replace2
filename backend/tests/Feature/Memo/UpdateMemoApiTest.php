<?php

namespace Tests\Feature\Memo;

use App\Models\Memo;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class UpdateMemoApiTest extends TestCase
{
    use RefreshDatabase;

    /** @param array<string, mixed> $data
     * @return TestResponse<\Illuminate\Http\Response>
     */
    private function spaPatch(string $url, array $data): TestResponse
    {
        return $this->withHeader('Referer', 'http://localhost')
            ->patchJson($url, $data);
    }

    // --- 正常系 ---

    public function test_正常な入力で更新され200が返ること(): void
    {
        $user = User::factory()->create();
        $memo = Memo::factory()->create(['user_id' => $user->id, 'title' => '旧タイトル']);
        $fetchedAt = $memo->updated_at?->copy()->addMinute()->toIso8601String();

        $response = $this->actingAs($user)->spaPatch("/api/memos/{$memo->id}", [
            'title' => '新タイトル',
            'fetched_at' => $fetchedAt,
        ]);

        $response->assertStatus(200);
        $response->assertJson(['messages' => ['更新しました。']]);
        $this->assertDatabaseHas('articles', ['id' => $memo->id, 'title' => '新タイトル']);
    }

    public function test_タグを付け替えると新しいタグはincrease外れたタグはdecreaseすること(): void
    {
        $user = User::factory()->create();
        $memo = Memo::factory()->create(['user_id' => $user->id]);
        $oldTag = Tag::factory()->usageCount(1)->create(['user_id' => $user->id]);
        $newTag = Tag::factory()->usageCount(0)->create(['user_id' => $user->id]);
        $memo->tags()->attach($oldTag->id);
        $fetchedAt = $memo->updated_at?->copy()->addMinute()->toIso8601String();

        $response = $this->actingAs($user)->spaPatch("/api/memos/{$memo->id}", [
            'tags' => [$newTag->id],
            'fetched_at' => $fetchedAt,
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('tag_statuses', ['tag_id' => $oldTag->id, 'count' => 0]);
        $this->assertDatabaseHas('tag_statuses', ['tag_id' => $newTag->id, 'count' => 1]);
        $this->assertDatabaseHas('article_tags', ['article_id' => $memo->id, 'tag_id' => $newTag->id]);
        $this->assertDatabaseMissing('article_tags', ['article_id' => $memo->id, 'tag_id' => $oldTag->id]);
    }

    public function test_タグを付け替えてもタグのupdated_atは更新されないこと(): void
    {
        $user = User::factory()->create();
        $memo = Memo::factory()->create(['user_id' => $user->id]);
        $oldTag = Tag::factory()->usageCount(1)->create(['user_id' => $user->id]);
        $newTag = Tag::factory()->create(['user_id' => $user->id]);
        $memo->tags()->attach($oldTag->id);
        $oldTagUpdatedAt = $oldTag->updated_at?->toIso8601String();
        $newTagUpdatedAt = $newTag->updated_at?->toIso8601String();

        $this->travel(1)->minutes();

        $response = $this->actingAs($user)->spaPatch("/api/memos/{$memo->id}", [
            'tags' => [$newTag->id],
            'fetched_at' => now()->toIso8601String(),
        ]);

        $response->assertStatus(200);
        $this->assertSame($oldTagUpdatedAt, $oldTag->fresh()?->updated_at?->toIso8601String());
        $this->assertSame($newTagUpdatedAt, $newTag->fresh()?->updated_at?->toIso8601String());
    }

    public function test_タグを付け替えて使用回数が増減してもタグのedited_atは更新されないこと(): void
    {
        $user = User::factory()->create();
        $memo = Memo::factory()->create(['user_id' => $user->id]);
        $oldTag = Tag::factory()->usageCount(1)->create(['user_id' => $user->id]);
        $newTag = Tag::factory()->create(['user_id' => $user->id]);
        $memo->tags()->attach($oldTag->id);
        $oldTagEditedAt = $oldTag->fresh()?->edited_at?->toIso8601String();
        $newTagEditedAt = $newTag->fresh()?->edited_at?->toIso8601String();

        $this->travel(1)->minutes();

        $response = $this->actingAs($user)->spaPatch("/api/memos/{$memo->id}", [
            'tags' => [$newTag->id],
            'fetched_at' => now()->toIso8601String(),
        ]);

        $response->assertStatus(200);
        $freshOldTag = $oldTag->fresh(['status']);
        $freshNewTag = $newTag->fresh(['status']);
        $this->assertSame(0, $freshOldTag?->status?->count);
        $this->assertSame(1, $freshNewTag?->status?->count);
        $this->assertNotNull($oldTagEditedAt);
        $this->assertSame($oldTagEditedAt, $freshOldTag->edited_at?->toIso8601String());
        $this->assertSame($newTagEditedAt, $freshNewTag->edited_at?->toIso8601String());
    }

    public function test_タグを全て外すとhas_tagsがfalseになること(): void
    {
        $user = User::factory()->create();
        $memo = Memo::factory()->create(['user_id' => $user->id]);
        $tag = Tag::factory()->usageCount(1)->create(['user_id' => $user->id]);
        $memo->tags()->attach($tag->id);

        $response = $this->actingAs($user)->spaPatch("/api/memos/{$memo->id}", [
            'tags' => [],
            'fetched_at' => now()->toIso8601String(),
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('article_statuses', ['article_id' => $memo->id, 'has_tags' => false]);
    }

    public function test_タグ無しのメモにタグを付けるとhas_tagsがtrueになること(): void
    {
        $user = User::factory()->create();
        $memo = Memo::factory()->create(['user_id' => $user->id]);
        $tag = Tag::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->spaPatch("/api/memos/{$memo->id}", [
            'tags' => [$tag->id],
            'fetched_at' => now()->toIso8601String(),
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('article_statuses', ['article_id' => $memo->id, 'has_tags' => true]);
    }

    public function test_タグだけを付け替えるとupdated_atが更新されること(): void
    {
        $user = User::factory()->create();
        $memo = Memo::factory()->create(['user_id' => $user->id]);
        $tag = Tag::factory()->create(['user_id' => $user->id]);
        $beforeUpdatedAt = $memo->updated_at?->toIso8601String();

        $this->travel(1)->minutes();

        $response = $this->actingAs($user)->spaPatch("/api/memos/{$memo->id}", [
            'tags' => [$tag->id],
            'fetched_at' => now()->toIso8601String(),
        ]);

        $response->assertStatus(200);
        $this->assertNotSame($beforeUpdatedAt, $memo->fresh()?->updated_at?->toIso8601String());
    }

    public function test_タグを同じ内容で送るとupdated_atが更新されないこと(): void
    {
        $user = User::factory()->create();
        $memo = Memo::factory()->create(['user_id' => $user->id]);
        $tag = Tag::factory()->usageCount(1)->create(['user_id' => $user->id]);
        $memo->tags()->attach($tag->id);
        $beforeUpdatedAt = $memo->updated_at?->toIso8601String();

        $this->travel(1)->minutes();

        $response = $this->actingAs($user)->spaPatch("/api/memos/{$memo->id}", [
            'tags' => [$tag->id],
            'fetched_at' => now()->toIso8601String(),
        ]);

        $response->assertStatus(200);
        $this->assertSame($beforeUpdatedAt, $memo->fresh()?->updated_at?->toIso8601String());
    }

    // --- 異常系 ---

    public function test_更新対象のメモが存在しない場合404が返ること(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->spaPatch('/api/memos/9999', [
            'title' => '新タイトル',
            'fetched_at' => now()->toIso8601String(),
        ]);

        $response->assertStatus(404);
        $response->assertJson(['messages' => ['メモが見つかりませんでした。']]);
    }

    public function test_他人のメモを更新しようとすると404が返ること(): void
    {
        $owner = User::factory()->create();
        $memo = Memo::factory()->create(['user_id' => $owner->id]);
        $otherUser = User::factory()->create();
        $fetchedAt = $memo->updated_at?->copy()->addMinute()->toIso8601String();

        $response = $this->actingAs($otherUser)->spaPatch("/api/memos/{$memo->id}", [
            'title' => '新タイトル',
            'fetched_at' => $fetchedAt,
        ]);

        $response->assertStatus(404);
        $response->assertJson(['messages' => ['メモが見つかりませんでした。']]);
    }

    public function test_fetched_atより後に更新されていた場合409が返ること(): void
    {
        $user = User::factory()->create();
        $memo = Memo::factory()->create(['user_id' => $user->id]);
        $updatedAt = $memo->updated_at;
        if ($updatedAt === null) {
            $this->fail('updated_at was not set.');
        }
        $fetchedAt = $updatedAt->copy()->subMinute()->toIso8601String();

        $response = $this->actingAs($user)->spaPatch("/api/memos/{$memo->id}", [
            'title' => '新タイトル',
            'fetched_at' => $fetchedAt,
        ]);

        $response->assertStatus(409);
        $response->assertJson([
            'messages' => [
                '保存できませんでした。',
                '他の画面で記事が更新されています。',
                '競合状態を修正してください',
            ],
        ]);
        $response->assertJsonStructure(['saved']);
        $this->assertDatabaseMissing('articles', ['id' => $memo->id, 'title' => '新タイトル']);
    }

    public function test_未認証の場合401が返ること(): void
    {
        $memo = Memo::factory()->create();

        $response = $this->spaPatch("/api/memos/{$memo->id}", ['title' => '新タイトル']);

        $response->assertStatus(401);
    }
}
