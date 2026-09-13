<?php

namespace Tests\Feature\Memo;

use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class CreateMemoApiTest extends TestCase
{
    use RefreshDatabase;

    /** @param array<string, mixed> $data
     * @return TestResponse<\Illuminate\Http\Response>
     */
    private function spaPost(string $url, array $data): TestResponse
    {
        return $this->withHeader('Referer', 'http://localhost')
            ->postJson($url, $data);
    }

    // --- 正常系 ---

    /** Verify that valid input creates a memo and returns its ID. */
    public function test_正常な入力でメモが作成されidが返ること(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->spaPost('/api/memos', [
            'title' => '新規メモ',
            'body' => '本文',
            'stars' => 3,
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['id']);
    }

    /** Verify that creating a memo inserts an article record. */
    public function test_作成するとarticlesテーブルにレコードが作成されること(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->spaPost('/api/memos', [
            'title' => '新規メモ',
            'body' => '本文',
            'stars' => 3,
        ]);

        $this->assertDatabaseHas('articles', [
            'user_id' => $user->id,
            'title' => '新規メモ',
            'body' => '本文',
            'star' => 3,
        ]);
    }

    /** Verify that attached tags are linked and their counts increase. */
    public function test_タグを指定して作成すると紐付き該当タグのcountがincreaseすること(): void
    {
        $user = User::factory()->create();
        $tag = Tag::factory()->create(['user_id' => $user->id, 'count' => 0]);

        $response = $this->actingAs($user)->spaPost('/api/memos', [
            'title' => '新規メモ',
            'tags' => [$tag->id],
        ]);

        $memoId = $response->json('id');

        $this->assertDatabaseHas('article_tags', ['article_id' => $memoId, 'tag_id' => $tag->id]);
        $this->assertDatabaseHas('tags', ['id' => $tag->id, 'count' => 1]);
    }

    /** Verify that tags owned by another user are not attached. */
    public function test_他人のタグidを指定しても紐付かないこと(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $otherTag = Tag::factory()->create(['user_id' => $otherUser->id, 'count' => 0]);

        $response = $this->actingAs($user)->spaPost('/api/memos', [
            'title' => '新規メモ',
            'tags' => [$otherTag->id],
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['id']);
        $memoId = $response->json('id');
        $this->assertNotNull($memoId);

        $this->assertDatabaseMissing('article_tags', ['article_id' => $memoId, 'tag_id' => $otherTag->id]);
        $this->assertDatabaseHas('tags', ['id' => $otherTag->id, 'count' => 0]);
    }

    // --- 異常系 ---

    /** Verify that nonnumeric tag IDs return 422. */
    public function test_tagsに数値以外を含めると422が返ること(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->spaPost('/api/memos', ['tags' => ['abc']]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('tags.0');
    }

    /** Verify that memo creation requires authentication. */
    public function test_未認証の場合401が返ること(): void
    {
        $response = $this->spaPost('/api/memos', ['title' => '新規メモ']);

        $response->assertStatus(401);
    }
}
