<?php

namespace Tests\Feature\Tag;

use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class SearchTagsApiTest extends TestCase
{
    use RefreshDatabase;

    /** @return TestResponse<\Illuminate\Http\Response> */
    private function spaGet(string $url): TestResponse
    {
        return $this->withHeader('Referer', 'http://localhost')
            ->getJson($url);
    }

    // --- 正常系 ---

    public function test_キーワード無しでログインユーザーのタグが取得できること(): void
    {
        $user = User::factory()->create();
        Tag::factory()->create(['user_id' => $user->id, 'name' => 'フルーツ']);
        Tag::factory()->create(['user_id' => $user->id, 'name' => '野菜']);

        $response = $this->actingAs($user)->spaGet('/api/tags');

        $response->assertStatus(200);
        $response->assertJsonCount(2, 'tags');
    }

    public function test_andキーワードで絞り込めること(): void
    {
        $user = User::factory()->create();
        Tag::factory()->create(['user_id' => $user->id, 'name' => '酸っぱいフルーツ']);
        Tag::factory()->create(['user_id' => $user->id, 'name' => '甘いフルーツ']);
        Tag::factory()->create(['user_id' => $user->id, 'name' => '野菜']);

        $response = $this->actingAs($user)->spaGet('/api/tags?'.http_build_query(['keywords' => '酸っぱい フルーツ']));

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'tags');
        $response->assertJsonPath('tags.0.name', '酸っぱいフルーツ');
    }

    public function test_マイナスキーワードで除外できること(): void
    {
        $user = User::factory()->create();
        Tag::factory()->create(['user_id' => $user->id, 'name' => '酸っぱいフルーツ']);
        Tag::factory()->create(['user_id' => $user->id, 'name' => '甘いフルーツ']);

        $response = $this->actingAs($user)->spaGet('/api/tags?'.http_build_query(['keywords' => 'フルーツ -酸っぱい']));

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'tags');
        $response->assertJsonPath('tags.0.name', '甘いフルーツ');
    }

    public function test_item_numberで取得件数が制限されること(): void
    {
        $user = User::factory()->create();
        Tag::factory()->count(5)->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->spaGet('/api/tags?'.http_build_query(['item_number' => 2]));

        $response->assertStatus(200);
        $response->assertJsonCount(2, 'tags');
    }

    public function test_他ユーザーのタグは対象にならないこと(): void
    {
        $user = User::factory()->create();
        Tag::factory()->create(['user_id' => $user->id, 'name' => '自分のタグ']);

        $otherUser = User::factory()->create();
        Tag::factory()->create(['user_id' => $otherUser->id, 'name' => '他人のタグ']);

        $response = $this->actingAs($user)->spaGet('/api/tags');

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'tags');
    }

    // --- 異常系 ---

    public function test_該当タグが無い場合404が返ること(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->spaGet('/api/tags');

        $response->assertStatus(404);
        $response->assertJson(['messages' => ['見つかりませんでした。']]);
    }

    public function test_sortに不正な値を指定すると422が返ること(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->spaGet('/api/tags?'.http_build_query(['sort' => 'invalid_column']));

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('sort');
    }

    public function test_未認証の場合401が返ること(): void
    {
        $response = $this->spaGet('/api/tags');

        $response->assertStatus(401);
    }
}
