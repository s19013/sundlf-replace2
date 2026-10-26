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

    public function test_paginationに現在ページ・最終ページ・1ページの件数・総件数が含まれること(): void
    {
        $user = User::factory()->create();
        Tag::factory()->count(5)->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->spaGet('/api/tags?'.http_build_query(['item_number' => 2]));

        $response->assertStatus(200);
        $response->assertJsonCount(2, 'tags');
        $response->assertJson([
            'pagination' => [
                'current_page' => 1,
                'last_page' => 3,
                'per_page' => 2,
                'total' => 5,
            ],
        ]);
    }

    public function test_pageを指定すると該当ページのタグが取得できること(): void
    {
        $user = User::factory()->create();
        foreach (['a', 'b', 'c', 'd', 'e'] as $name) {
            Tag::factory()->create(['user_id' => $user->id, 'name' => $name]);
        }

        // name降順: e, d | c, b | a
        $response = $this->actingAs($user)->spaGet('/api/tags?'.http_build_query([
            'sort' => 'name',
            'item_number' => 2,
            'page' => 2,
        ]));

        $response->assertStatus(200);
        $response->assertJsonCount(2, 'tags');
        $response->assertJsonPath('tags.0.name', 'c');
        $response->assertJsonPath('tags.1.name', 'b');
        $response->assertJsonPath('pagination.current_page', 2);
    }

    public function test_最終ページは端数の件数だけ取得できること(): void
    {
        $user = User::factory()->create();
        foreach (['a', 'b', 'c', 'd', 'e'] as $name) {
            Tag::factory()->create(['user_id' => $user->id, 'name' => $name]);
        }

        $response = $this->actingAs($user)->spaGet('/api/tags?'.http_build_query([
            'sort' => 'name',
            'item_number' => 2,
            'page' => 3,
        ]));

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'tags');
        $response->assertJsonPath('tags.0.name', 'a');
        $response->assertJson(['pagination' => ['current_page' => 3, 'last_page' => 3]]);
    }

    public function test_キーワードで絞り込んだ結果の件数がtotalになること(): void
    {
        $user = User::factory()->create();
        foreach (['フルーツA', 'フルーツB', 'フルーツC', '野菜A', '野菜B'] as $name) {
            Tag::factory()->create(['user_id' => $user->id, 'name' => $name]);
        }

        $response = $this->actingAs($user)->spaGet('/api/tags?'.http_build_query([
            'keywords' => 'フルーツ',
            'item_number' => 2,
        ]));

        $response->assertStatus(200);
        $response->assertJsonCount(2, 'tags');
        $response->assertJson(['pagination' => ['last_page' => 2, 'total' => 3]]);
    }

    public function test_paginationのtotalに他ユーザーのタグが含まれないこと(): void
    {
        $user = User::factory()->create();
        Tag::factory()->count(2)->create(['user_id' => $user->id]);

        $otherUser = User::factory()->create();
        Tag::factory()->count(3)->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($user)->spaGet('/api/tags');

        $response->assertStatus(200);
        $response->assertJson(['pagination' => ['total' => 2]]);
    }

    public function test_countが同値の場合はid降順で並び全ページで重複なく取得できること(): void
    {
        $user = User::factory()->create();
        $tags = Tag::factory()->count(5)->create(['user_id' => $user->id, 'count' => 0]);

        $ids = [];
        foreach ([1, 2, 3] as $page) {
            $response = $this->actingAs($user)->spaGet('/api/tags?'.http_build_query([
                'sort' => 'count',
                'item_number' => 2,
                'page' => $page,
            ]));

            $response->assertStatus(200);
            $ids = array_merge($ids, $response->json('tags.*.id'));
        }

        $this->assertSame($tags->pluck('id')->sortDesc()->values()->all(), $ids);
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

    public function test_存在しないページを指定すると404が返ること(): void
    {
        $user = User::factory()->create();
        Tag::factory()->count(3)->create(['user_id' => $user->id]);

        // item_number=2なので最終ページは2
        $response = $this->actingAs($user)->spaGet('/api/tags?'.http_build_query([
            'item_number' => 2,
            'page' => 3,
        ]));

        $response->assertStatus(404);
        $response->assertJson(['messages' => ['見つかりませんでした。']]);
    }

    public function test_pageに0を指定すると422が返ること(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->spaGet('/api/tags?'.http_build_query(['page' => 0]));

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('page');
    }

    public function test_pageに数値以外を指定すると422が返ること(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->spaGet('/api/tags?'.http_build_query(['page' => 'abc']));

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('page');
    }

    public function test_未認証の場合401が返ること(): void
    {
        $response = $this->spaGet('/api/tags');

        $response->assertStatus(401);
    }
}
