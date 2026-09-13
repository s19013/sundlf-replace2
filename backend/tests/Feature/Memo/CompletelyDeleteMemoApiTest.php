<?php

namespace Tests\Feature\Memo;

use App\Models\Memo;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class CompletelyDeleteMemoApiTest extends TestCase
{
    use RefreshDatabase;

    /** @return TestResponse<\Illuminate\Http\Response> */
    private function spaDelete(string $url): TestResponse
    {
        return $this->withHeader('Referer', 'http://localhost')
            ->deleteJson($url);
    }

    // --- 正常系 ---

    public function test_論理削除済みメモを完全削除でき200が返ること(): void
    {
        $user = User::factory()->create();
        $memo = Memo::factory()->create(['user_id' => $user->id, 'title' => '完全削除対象']);
        $memo->delete();

        $response = $this->actingAs($user)->spaDelete("/api/memos/{$memo->id}/completely");

        $response->assertStatus(200);
        $response->assertJson(['messages' => ['完全削除対象を完全削除しました。']]);
        $this->assertDatabaseMissing('articles', ['id' => $memo->id]);
    }

    public function test_完全削除すると中間テーブルの紐付けも削除されること(): void
    {
        $user = User::factory()->create();
        $memo = Memo::factory()->create(['user_id' => $user->id]);
        $tag = Tag::factory()->create(['user_id' => $user->id]);
        $memo->tags()->attach($tag->id);
        $memo->delete();

        $this->actingAs($user)->spaDelete("/api/memos/{$memo->id}/completely");

        $this->assertDatabaseMissing('article_tags', ['article_id' => $memo->id]);
    }

    // --- 異常系 ---

    public function test_存在しないメモの場合404が返ること(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->spaDelete('/api/memos/9999/completely');

        $response->assertStatus(404);
    }

    public function test_他人のメモを完全削除しようとすると403が返ること(): void
    {
        $owner = User::factory()->create();
        $memo = Memo::factory()->create(['user_id' => $owner->id]);
        $memo->delete();
        $otherUser = User::factory()->create();

        $response = $this->actingAs($otherUser)->spaDelete("/api/memos/{$memo->id}/completely");

        $response->assertStatus(403);
        $response->assertJson(['messages' => ['このメモは完全削除できません。']]);
    }

    public function test_論理削除されていないメモの場合422が返ること(): void
    {
        $user = User::factory()->create();
        $memo = Memo::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->spaDelete("/api/memos/{$memo->id}/completely");

        $response->assertStatus(422);
        $response->assertJson(['messages' => ['ゴミ箱にないメモは完全削除できません。']]);
        $this->assertDatabaseHas('articles', ['id' => $memo->id]);
    }

    public function test_未認証の場合401が返ること(): void
    {
        $memo = Memo::factory()->create();

        $response = $this->spaDelete("/api/memos/{$memo->id}/completely");

        $response->assertStatus(401);
    }
}
