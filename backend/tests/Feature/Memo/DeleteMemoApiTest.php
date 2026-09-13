<?php

namespace Tests\Feature\Memo;

use App\Models\Memo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class DeleteMemoApiTest extends TestCase
{
    use RefreshDatabase;

    /** @return TestResponse<\Illuminate\Http\Response> */
    private function spaDelete(string $url): TestResponse
    {
        return $this->withHeader('Referer', 'http://localhost')
            ->deleteJson($url);
    }

    // --- 正常系 ---

    public function test_正常な削除で200が返ること(): void
    {
        $user = User::factory()->create();
        $memo = Memo::factory()->create(['user_id' => $user->id, 'title' => '削除対象メモ']);

        $response = $this->actingAs($user)->spaDelete("/api/memos/{$memo->id}");

        $response->assertStatus(200);
        $response->assertJson(['messages' => ['削除対象メモ を削除しました。']]);
    }

    public function test_削除するとarticlesテーブルが論理削除されること(): void
    {
        $user = User::factory()->create();
        $memo = Memo::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)->spaDelete("/api/memos/{$memo->id}");

        $this->assertSoftDeleted('articles', ['id' => $memo->id]);
    }

    // --- 異常系 ---

    public function test_削除対象のメモが存在しない場合404が返ること(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->spaDelete('/api/memos/9999');

        $response->assertStatus(404);
        $response->assertJson(['messages' => ['メモが見つかりませんでした。']]);
    }

    public function test_既に論理削除済みのメモの場合404が返ること(): void
    {
        $user = User::factory()->create();
        $memo = Memo::factory()->create(['user_id' => $user->id]);
        $memo->delete();

        $response = $this->actingAs($user)->spaDelete("/api/memos/{$memo->id}");

        $response->assertStatus(404);
    }

    public function test_他人のメモを削除しようとすると404が返ること(): void
    {
        $owner = User::factory()->create();
        $memo = Memo::factory()->create(['user_id' => $owner->id]);
        $otherUser = User::factory()->create();

        $response = $this->actingAs($otherUser)->spaDelete("/api/memos/{$memo->id}");

        $response->assertStatus(404);
        $response->assertJson(['messages' => ['このメモは削除できません。']]);
        $this->assertDatabaseHas('articles', ['id' => $memo->id, 'deleted_at' => null]);
    }

    public function test_未認証の場合401が返ること(): void
    {
        $memo = Memo::factory()->create();

        $response = $this->spaDelete("/api/memos/{$memo->id}");

        $response->assertStatus(401);
    }
}
