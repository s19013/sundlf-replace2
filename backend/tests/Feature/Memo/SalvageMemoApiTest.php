<?php

namespace Tests\Feature\Memo;

use App\Models\Memo;
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

    // --- 異常系 ---

    public function test_存在しないメモの場合404が返ること(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->spaPost('/api/memos/9999/salvage');

        $response->assertStatus(404);
        $response->assertJson(['messages' => ['メモが見つかりませんでした。']]);
    }

    public function test_論理削除されていないメモでも復元できること(): void
    {
        $user = User::factory()->create();
        $memo = Memo::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->spaPost("/api/memos/{$memo->id}/salvage");

        $response->assertStatus(200);
    }

    public function test_他人のメモを復元しようとすると403が返ること(): void
    {
        $owner = User::factory()->create();
        $memo = Memo::factory()->create(['user_id' => $owner->id]);
        $memo->delete();
        $otherUser = User::factory()->create();

        $response = $this->actingAs($otherUser)->spaPost("/api/memos/{$memo->id}/salvage");

        $response->assertStatus(403);
        $response->assertJson(['messages' => ['このメモは復元できません。']]);
    }

    public function test_未認証の場合401が返ること(): void
    {
        $memo = Memo::factory()->create();
        $memo->delete();

        $response = $this->spaPost("/api/memos/{$memo->id}/salvage");

        $response->assertStatus(401);
    }
}
