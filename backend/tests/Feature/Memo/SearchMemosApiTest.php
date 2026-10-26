<?php

namespace Tests\Feature\Memo;

use App\Models\Memo;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class SearchMemosApiTest extends TestCase
{
    use RefreshDatabase;

    /** @return TestResponse<\Illuminate\Http\Response> */
    private function spaGet(string $url): TestResponse
    {
        return $this->withHeader('Referer', 'http://localhost')
            ->getJson($url);
    }

    // --- 正常系 ---

    public function test_キーワード無しでログインユーザーのメモが取得できること(): void
    {
        $user = User::factory()->create();
        Memo::factory()->create(['user_id' => $user->id]);
        Memo::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->spaGet('/api/memos/search');

        $response->assertStatus(200);
        $response->assertJsonCount(2, 'memos');
    }

    public function test_他ユーザーのメモは対象にならないこと(): void
    {
        $user = User::factory()->create();
        Memo::factory()->create(['user_id' => $user->id]);

        $otherUser = User::factory()->create();
        Memo::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($user)->spaGet('/api/memos/search');

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'memos');
    }

    public function test_andキーワードで絞り込めること(): void
    {
        $user = User::factory()->create();
        Memo::factory()->create(['user_id' => $user->id, 'title' => '酸っぱいフルーツ']);
        Memo::factory()->create(['user_id' => $user->id, 'title' => '甘いフルーツ']);
        Memo::factory()->create(['user_id' => $user->id, 'title' => '野菜']);

        $response = $this->actingAs($user)->spaGet('/api/memos/search?'.http_build_query(['keyword' => '酸っぱい フルーツ']));

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'memos');
        $response->assertJsonPath('memos.0.title', '酸っぱいフルーツ');
    }

    public function test_マイナスキーワードで除外できること(): void
    {
        $user = User::factory()->create();
        Memo::factory()->create(['user_id' => $user->id, 'title' => '酸っぱいフルーツ']);
        Memo::factory()->create(['user_id' => $user->id, 'title' => '甘いフルーツ']);

        $response = $this->actingAs($user)->spaGet('/api/memos/search?'.http_build_query(['keyword' => 'フルーツ -酸っぱい']));

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'memos');
        $response->assertJsonPath('memos.0.title', '甘いフルーツ');
    }

    public function test_targetにbodyを指定すると本文のみ検索されること(): void
    {
        $user = User::factory()->create();
        Memo::factory()->create(['user_id' => $user->id, 'title' => 'キーワード', 'body' => '無関係な本文']);
        Memo::factory()->create(['user_id' => $user->id, 'title' => '無関係なタイトル', 'body' => 'キーワードを含む本文']);

        $response = $this->actingAs($user)->spaGet('/api/memos/search?'.http_build_query(['keyword' => 'キーワード', 'target' => 'body']));

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'memos');
        $response->assertJsonPath('memos.0.body', 'キーワードを含む本文');
    }

    public function test_完全一致タグで絞り込めること(): void
    {
        $user = User::factory()->create();
        $tagA = Tag::factory()->create(['user_id' => $user->id]);
        $tagB = Tag::factory()->create(['user_id' => $user->id]);

        $memoWithBoth = Memo::factory()->create(['user_id' => $user->id]);
        $memoWithBoth->tags()->attach([$tagA->id, $tagB->id]);

        $memoWithOnlyA = Memo::factory()->create(['user_id' => $user->id]);
        $memoWithOnlyA->tags()->attach([$tagA->id]);

        $response = $this->actingAs($user)->spaGet('/api/memos/search?'.http_build_query([
            'exact_match_tags' => [$tagA->id, $tagB->id],
        ]));

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'memos');
        $response->assertJsonPath('memos.0.id', $memoWithBoth->id);
    }

    public function test_部分一致タグで絞り込めること(): void
    {
        $user = User::factory()->create();
        $tagA = Tag::factory()->create(['user_id' => $user->id]);
        $tagB = Tag::factory()->create(['user_id' => $user->id]);
        $tagC = Tag::factory()->create(['user_id' => $user->id]);

        $memoWithA = Memo::factory()->create(['user_id' => $user->id]);
        $memoWithA->tags()->attach([$tagA->id]);

        $memoWithC = Memo::factory()->create(['user_id' => $user->id]);
        $memoWithC->tags()->attach([$tagC->id]);

        $response = $this->actingAs($user)->spaGet('/api/memos/search?'.http_build_query([
            'partial_match_tags' => [$tagA->id, $tagB->id],
        ]));

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'memos');
        $response->assertJsonPath('memos.0.id', $memoWithA->id);
    }

    public function test_除外タグで絞り込めること(): void
    {
        $user = User::factory()->create();
        $tag = Tag::factory()->create(['user_id' => $user->id]);

        $memoWithTag = Memo::factory()->create(['user_id' => $user->id]);
        $memoWithTag->tags()->attach([$tag->id]);

        $memoWithoutTag = Memo::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->spaGet('/api/memos/search?'.http_build_query([
            'exclusion_match_tags' => [$tag->id],
        ]));

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'memos');
        $response->assertJsonPath('memos.0.id', $memoWithoutTag->id);
    }

    public function test_is_tag_not_attachedでタグ無しメモのみ絞り込めること(): void
    {
        $user = User::factory()->create();
        $tag = Tag::factory()->create(['user_id' => $user->id]);

        $memoWithTag = Memo::factory()->create(['user_id' => $user->id]);
        $memoWithTag->tags()->attach([$tag->id]);

        $memoWithoutTag = Memo::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->spaGet('/api/memos/search?'.http_build_query([
            'is_tag_not_attached' => '1',
        ]));

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'memos');
        $response->assertJsonPath('memos.0.id', $memoWithoutTag->id);
    }

    public function test_starsで星数を絞り込めること(): void
    {
        $user = User::factory()->create();
        Memo::factory()->create(['user_id' => $user->id, 'star' => 5]);
        Memo::factory()->create(['user_id' => $user->id, 'star' => 1]);

        $response = $this->actingAs($user)->spaGet('/api/memos/search?'.http_build_query(['stars' => 5]));

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'memos');
        $response->assertJsonPath('memos.0.star', 5);
    }

    public function test_is_in_trashboxでゴミ箱のメモのみ絞り込めること(): void
    {
        $user = User::factory()->create();
        $activeMemo = Memo::factory()->create(['user_id' => $user->id]);
        $trashedMemo = Memo::factory()->create(['user_id' => $user->id]);
        $trashedMemo->delete();

        $response = $this->actingAs($user)->spaGet('/api/memos/search?'.http_build_query(['is_in_trashbox' => '1']));

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'memos');
        $response->assertJsonPath('memos.0.id', $trashedMemo->id);
        $response->assertJsonMissing(['id' => $activeMemo->id]);
    }

    public function test_item_numberで取得件数が制限されること(): void
    {
        $user = User::factory()->create();
        Memo::factory()->count(5)->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->spaGet('/api/memos/search?'.http_build_query(['item_number' => 2]));

        $response->assertStatus(200);
        $response->assertJsonCount(2, 'memos');
    }

    public function test_sortにtitleを指定すると降順で取得できること(): void
    {
        $user = User::factory()->create();
        Memo::factory()->create(['user_id' => $user->id, 'title' => 'あああ']);
        Memo::factory()->create(['user_id' => $user->id, 'title' => 'ううう']);

        $response = $this->actingAs($user)->spaGet('/api/memos/search?'.http_build_query(['sort' => 'title']));

        $response->assertStatus(200);
        $response->assertJsonPath('memos.0.title', 'ううう');
    }

    public function test_paginationに現在ページ・最終ページ・1ページの件数・総件数が含まれること(): void
    {
        $user = User::factory()->create();
        Memo::factory()->count(5)->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->spaGet('/api/memos/search?'.http_build_query(['item_number' => 2]));

        $response->assertStatus(200);
        $response->assertJsonCount(2, 'memos');
        $response->assertJson([
            'pagination' => [
                'current_page' => 1,
                'last_page' => 3,
                'per_page' => 2,
                'total' => 5,
            ],
        ]);
    }

    public function test_pageを指定すると該当ページのメモが取得できること(): void
    {
        $user = User::factory()->create();
        foreach (['a', 'b', 'c', 'd', 'e'] as $title) {
            Memo::factory()->create(['user_id' => $user->id, 'title' => $title]);
        }

        // title降順: e, d | c, b | a
        $response = $this->actingAs($user)->spaGet('/api/memos/search?'.http_build_query([
            'sort' => 'title',
            'item_number' => 2,
            'page' => 2,
        ]));

        $response->assertStatus(200);
        $response->assertJsonCount(2, 'memos');
        $response->assertJsonPath('memos.0.title', 'c');
        $response->assertJsonPath('memos.1.title', 'b');
        $response->assertJsonPath('pagination.current_page', 2);
    }

    public function test_最終ページは端数の件数だけ取得できること(): void
    {
        $user = User::factory()->create();
        foreach (['a', 'b', 'c', 'd', 'e'] as $title) {
            Memo::factory()->create(['user_id' => $user->id, 'title' => $title]);
        }

        $response = $this->actingAs($user)->spaGet('/api/memos/search?'.http_build_query([
            'sort' => 'title',
            'item_number' => 2,
            'page' => 3,
        ]));

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'memos');
        $response->assertJsonPath('memos.0.title', 'a');
        $response->assertJson(['pagination' => ['current_page' => 3, 'last_page' => 3]]);
    }

    public function test_キーワードで絞り込んだ結果の件数がtotalになること(): void
    {
        $user = User::factory()->create();
        Memo::factory()->count(3)->create(['user_id' => $user->id, 'title' => 'フルーツ']);
        Memo::factory()->count(2)->create(['user_id' => $user->id, 'title' => '野菜']);

        $response = $this->actingAs($user)->spaGet('/api/memos/search?'.http_build_query([
            'keyword' => 'フルーツ',
            'item_number' => 2,
        ]));

        $response->assertStatus(200);
        $response->assertJsonCount(2, 'memos');
        $response->assertJson(['pagination' => ['last_page' => 2, 'total' => 3]]);
    }

    public function test_paginationのtotalに他ユーザーのメモが含まれないこと(): void
    {
        $user = User::factory()->create();
        Memo::factory()->count(2)->create(['user_id' => $user->id]);

        $otherUser = User::factory()->create();
        Memo::factory()->count(3)->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($user)->spaGet('/api/memos/search');

        $response->assertStatus(200);
        $response->assertJson(['pagination' => ['total' => 2]]);
    }

    public function test_ゴミ箱検索でもpaginationが返ること(): void
    {
        $user = User::factory()->create();
        Memo::factory()->create(['user_id' => $user->id]);

        $trashedMemos = Memo::factory()->count(3)->create(['user_id' => $user->id]);
        foreach ($trashedMemos as $trashedMemo) {
            $trashedMemo->delete();
        }

        $response = $this->actingAs($user)->spaGet('/api/memos/search?'.http_build_query([
            'is_in_trashbox' => '1',
            'item_number' => 2,
        ]));

        $response->assertStatus(200);
        $response->assertJsonCount(2, 'memos');
        $response->assertJson(['pagination' => ['last_page' => 2, 'total' => 3]]);
    }

    public function test_updated_atが同値の場合はid降順で並び全ページで重複なく取得できること(): void
    {
        $user = User::factory()->create();
        $memos = Memo::factory()->count(5)->create([
            'user_id' => $user->id,
            'updated_at' => '2026-01-01 00:00:00',
        ]);

        $ids = [];
        foreach ([1, 2, 3] as $page) {
            $response = $this->actingAs($user)->spaGet('/api/memos/search?'.http_build_query([
                'sort' => 'updated_at',
                'item_number' => 2,
                'page' => $page,
            ]));

            $response->assertStatus(200);
            $ids = array_merge($ids, $response->json('memos.*.id'));
        }

        $this->assertSame($memos->pluck('id')->sortDesc()->values()->all(), $ids);
    }

    public function test_sortにrandomを指定してもpaginationが返ること(): void
    {
        $user = User::factory()->create();
        Memo::factory()->count(3)->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->spaGet('/api/memos/search?'.http_build_query([
            'sort' => 'random',
            'item_number' => 2,
        ]));

        $response->assertStatus(200);
        $response->assertJsonCount(2, 'memos');
        $response->assertJson(['pagination' => ['last_page' => 2, 'total' => 3]]);
    }

    // --- 異常系 ---

    public function test_該当メモが無い場合404が返ること(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->spaGet('/api/memos/search');

        $response->assertStatus(404);
        $response->assertJson(['messages' => ['見つかりませんでした。']]);
    }

    public function test_sortに不正な値を指定すると422が返ること(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->spaGet('/api/memos/search?'.http_build_query(['sort' => 'invalid_column']));

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('sort');
    }

    public function test_存在しないページを指定すると404が返ること(): void
    {
        $user = User::factory()->create();
        Memo::factory()->count(3)->create(['user_id' => $user->id]);

        // item_number=2なので最終ページは2
        $response = $this->actingAs($user)->spaGet('/api/memos/search?'.http_build_query([
            'item_number' => 2,
            'page' => 3,
        ]));

        $response->assertStatus(404);
        $response->assertJson(['messages' => ['見つかりませんでした。']]);
    }

    public function test_pageに0を指定すると422が返ること(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->spaGet('/api/memos/search?'.http_build_query(['page' => 0]));

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('page');
    }

    public function test_pageに数値以外を指定すると422が返ること(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->spaGet('/api/memos/search?'.http_build_query(['page' => 'abc']));

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('page');
    }

    public function test_未認証の場合401が返ること(): void
    {
        $response = $this->spaGet('/api/memos/search');

        $response->assertStatus(401);
    }
}
