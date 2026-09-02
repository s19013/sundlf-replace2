<?php

namespace Tests\Feature\Tag;

use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class AllTagsApiTest extends TestCase
{
    use RefreshDatabase;

    /** @return TestResponse<\Illuminate\Http\Response> */
    private function spaGet(string $url): TestResponse
    {
        return $this->withHeader('Referer', 'http://localhost')
            ->getJson($url);
    }

    // --- 正常系 ---

    public function test_ログインユーザーの全タグが取得できること(): void
    {
        $user = User::factory()->create();
        Tag::factory()->create(['user_id' => $user->id, 'name' => 'タグA']);
        Tag::factory()->create(['user_id' => $user->id, 'name' => 'タグB']);

        $response = $this->actingAs($user)->spaGet('/api/tags/all');

        $response->assertStatus(200);
        $response->assertJsonCount(2, 'tags');
        $response->assertJsonStructure([
            'tags' => [['id', 'name', 'count', 'created_at', 'updated_at']],
        ]);
    }

    public function test_他ユーザーのタグは含まれないこと(): void
    {
        $user = User::factory()->create();
        Tag::factory()->create(['user_id' => $user->id, 'name' => '自分のタグ']);

        $otherUser = User::factory()->create();
        Tag::factory()->create(['user_id' => $otherUser->id, 'name' => '他人のタグ']);

        $response = $this->actingAs($user)->spaGet('/api/tags/all');

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'tags');
        $response->assertJsonPath('tags.0.name', '自分のタグ');
    }

    // --- 異常系 ---

    public function test_タグが1件もない場合404が返ること(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->spaGet('/api/tags/all');

        $response->assertStatus(404);
        $response->assertJson(['messages' => ['見つかりませんでした。']]);
    }

    public function test_未認証の場合401が返ること(): void
    {
        $response = $this->spaGet('/api/tags/all');

        $response->assertStatus(401);
    }
}
