<?php

namespace Tests\Feature\Memo;

use App\Models\Memo;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class SalvageMemoApiTest extends TestCase
{
    use RefreshDatabase;

    /** @return TestResponse<\Illuminate\Http\Response> */
    private function spaPost(string $url): TestResponse
    {
        return $this->withHeader('Referer', 'http://localhost')
            ->postJson($url);
    }

    // --- 正常系 ---

    /** Verify that a trashed memo can be restored. */
    public function test_論理削除済みメモを復元でき200が返ること(): void
    {
        $user = User::factory()->create();
        $memo = Memo::factory()->create(['user_id' => $user->id, 'title' => '復元対象']);
        $memo->delete();

        $response = $this->actingAs($user)->spaPost("/api/memos/{$memo->id}/salvage");

        $response->assertStatus(200);
        $response->assertJson(['messages' => ['復元対象 を復元しました。']]);
        $this->assertDatabaseHas('articles', ['id' => $memo->id, 'deleted_at' => null]);
    }

    /** Verify that restoring a memo increments every attached tag count. */
    public function test_複数タグ付きメモを復元するとすべてのタグのcountがincreaseすること(): void
    {
        $user = User::factory()->create();
        $memo = Memo::factory()->create(['user_id' => $user->id]);
        $tag1 = Tag::factory()->create(['user_id' => $user->id, 'count' => 0]);
        $tag2 = Tag::factory()->create(['user_id' => $user->id, 'count' => 0]);
        $memo->tags()->attach([$tag1->id, $tag2->id]);
        $memo->delete();

        $this->actingAs($user)->spaPost("/api/memos/{$memo->id}/salvage");

        $this->assertDatabaseHas('tags', ['id' => $tag1->id, 'count' => 1]);
        $this->assertDatabaseHas('tags', ['id' => $tag2->id, 'count' => 1]);
    }

    /** Verify that a memo without tags can be restored. */
    public function test_タグなしメモを復元できること(): void
    {
        $user = User::factory()->create();
        $memo = Memo::factory()->create(['user_id' => $user->id]);
        $memo->delete();

        $response = $this->actingAs($user)->spaPost("/api/memos/{$memo->id}/salvage");

        $response->assertStatus(200);
        $this->assertDatabaseHas('articles', ['id' => $memo->id, 'deleted_at' => null]);
    }

    // --- 異常系 ---

    /** Verify that restoring a nonexistent memo returns 404. */
    public function test_存在しないメモの場合404が返ること(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->spaPost('/api/memos/9999/salvage');

        $response->assertStatus(404);
        $response->assertJson(['messages' => ['メモが見つかりませんでした。']]);
    }

    /** Verify that restoring an active memo returns 422. */
    public function test_論理削除されていないメモの場合422が返ること(): void
    {
        $user = User::factory()->create();
        $memo = Memo::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->spaPost("/api/memos/{$memo->id}/salvage");

        $response->assertStatus(422);
        $response->assertJson(['messages' => ['ゴミ箱にないメモは復元できません。']]);
    }

    /** Verify that another user's memo cannot be restored. */
    public function test_他人のメモを復元しようとすると404が返ること(): void
    {
        $owner = User::factory()->create();
        $memo = Memo::factory()->create(['user_id' => $owner->id]);
        $memo->delete();
        $otherUser = User::factory()->create();

        $response = $this->actingAs($otherUser)->spaPost("/api/memos/{$memo->id}/salvage");

        $response->assertStatus(404);
        $response->assertJson(['messages' => ['このメモは復元できません。']]);
    }

    /** Verify that restoring a memo requires authentication. */
    public function test_未認証の場合401が返ること(): void
    {
        $memo = Memo::factory()->create();
        $memo->delete();

        $response = $this->spaPost("/api/memos/{$memo->id}/salvage");

        $response->assertStatus(401);
    }
}
