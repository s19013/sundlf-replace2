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

    /** Verify that valid input updates a memo. */
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

    /** Verify that replacing tags adjusts both tag counts. */
    public function test_タグを付け替えると新しいタグはincrease外れたタグはdecreaseすること(): void
    {
        $user = User::factory()->create();
        $memo = Memo::factory()->create(['user_id' => $user->id]);
        $oldTag = Tag::factory()->create(['user_id' => $user->id, 'count' => 1]);
        $newTag = Tag::factory()->create(['user_id' => $user->id, 'count' => 0]);
        $memo->tags()->attach($oldTag->id);
        $fetchedAt = $memo->updated_at?->copy()->addMinute()->toIso8601String();

        $response = $this->actingAs($user)->spaPatch("/api/memos/{$memo->id}", [
            'tags' => [$newTag->id],
            'fetched_at' => $fetchedAt,
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('tags', ['id' => $oldTag->id, 'count' => 0]);
        $this->assertDatabaseHas('tags', ['id' => $newTag->id, 'count' => 1]);
        $this->assertDatabaseHas('article_tags', ['article_id' => $memo->id, 'tag_id' => $newTag->id]);
        $this->assertDatabaseMissing('article_tags', ['article_id' => $memo->id, 'tag_id' => $oldTag->id]);
    }

    // --- 異常系 ---

    /** Verify that updating a nonexistent memo returns 404. */
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

    /** Verify that another user's memo cannot be updated. */
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
        $response->assertJson(['messages' => ['このメモは更新できません。']]);
    }

    /** Verify that a stale update timestamp returns 409. */
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

    /** Verify that memo updates require authentication. */
    public function test_未認証の場合401が返ること(): void
    {
        $memo = Memo::factory()->create();

        $response = $this->spaPatch("/api/memos/{$memo->id}", ['title' => '新タイトル']);

        $response->assertStatus(401);
    }
}
