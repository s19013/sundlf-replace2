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

    public function test_他人のタグidを指定しても紐付かないこと(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $otherTag = Tag::factory()->create(['user_id' => $otherUser->id, 'count' => 0]);

        $response = $this->actingAs($user)->spaPost('/api/memos', [
            'title' => '新規メモ',
            'tags' => [$otherTag->id],
        ]);

        $memoId = $response->json('id');

        $this->assertDatabaseMissing('article_tags', ['article_id' => $memoId, 'tag_id' => $otherTag->id]);
        $this->assertDatabaseHas('tags', ['id' => $otherTag->id, 'count' => 0]);
    }

    // --- 異常系 ---

    public function test_tagsに数値以外を含めると422が返ること(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->spaPost('/api/memos', ['tags' => ['abc']]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('tags.0');
    }

    public function test_未認証の場合401が返ること(): void
    {
        $response = $this->spaPost('/api/memos', ['title' => '新規メモ']);

        $response->assertStatus(401);
    }
}
