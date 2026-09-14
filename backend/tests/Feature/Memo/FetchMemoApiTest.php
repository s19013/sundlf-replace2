<?php

namespace Tests\Feature\Memo;

use App\Models\Memo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class FetchMemoApiTest extends TestCase
{
    use RefreshDatabase;

    /** @return TestResponse<\Illuminate\Http\Response> */
    private function spaGet(string $url): TestResponse
    {
        return $this->withHeader('Referer', 'http://localhost')
            ->getJson($url);
    }

    // --- 正常系 ---

    public function test_正常に取得でき200が返ること(): void
    {
        $user = User::factory()->create();
        $memo = Memo::factory()->create(['user_id' => $user->id, 'title' => '対象メモ']);

        $response = $this->actingAs($user)->spaGet("/api/memos/{$memo->id}");

        $response->assertStatus(200);
        $response->assertJsonPath('memo.title', '対象メモ');
    }

    public function test_fetched_atが更新前であれば200が返ること(): void
    {
        $user = User::factory()->create();
        $memo = Memo::factory()->create(['user_id' => $user->id]);
        $updatedAt = $memo->updated_at;
        if ($updatedAt === null) {
            $this->fail('updated_at was not set.');
        }
        $fetchedAt = $updatedAt->copy()->addMinute()->toIso8601String();

        $response = $this->actingAs($user)->spaGet("/api/memos/{$memo->id}?".http_build_query(['fetched_at' => $fetchedAt]));

        $response->assertStatus(200);
    }

    // --- 異常系 ---

    public function test_存在しないメモの場合404が返ること(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->spaGet('/api/memos/9999');

        $response->assertStatus(404);
        $response->assertJson(['messages' => ['メモが見つかりませんでした。']]);
    }

    public function test_論理削除済みメモの場合404が返ること(): void
    {
        $user = User::factory()->create();
        $memo = Memo::factory()->create(['user_id' => $user->id]);
        $memo->delete();

        $response = $this->actingAs($user)->spaGet("/api/memos/{$memo->id}");

        $response->assertStatus(404);
    }

    public function test_他人のメモを取得しようとすると404が返ること(): void
    {
        $owner = User::factory()->create();
        $memo = Memo::factory()->create(['user_id' => $owner->id]);
        $otherUser = User::factory()->create();

        $response = $this->actingAs($otherUser)->spaGet("/api/memos/{$memo->id}");

        $response->assertStatus(404);
        $response->assertJson(['messages' => ['このメモは取得できません。']]);
    }

    public function test_fetched_atより後に更新されていた場合409が返ること(): void
    {
        $user = User::factory()->create();
        $memo = Memo::factory()->create(['user_id' => $user->id]);
        $updatedAt = $memo->updated_at;
        if ($updatedAt === null) {
            $this->fail('updated_at was not set.');
        }
        $fetchedAt = $updatedAt->copy()->subMinute()->toIso8601String();

        $response = $this->actingAs($user)->spaGet("/api/memos/{$memo->id}?".http_build_query(['fetched_at' => $fetchedAt]));

        $response->assertStatus(409);
        $response->assertJson(['messages' => ['他の画面でメモが更新されています。反映しますか?']]);
    }

    public function test_未認証の場合401が返ること(): void
    {
        $memo = Memo::factory()->create();

        $response = $this->spaGet("/api/memos/{$memo->id}");

        $response->assertStatus(401);
    }
}
