<?php

namespace Tests\Feature\Memo;

use App\Models\Memo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class IncreaseMemoViewCountApiTest extends TestCase
{
    use RefreshDatabase;

    /** @return TestResponse<\Illuminate\Http\Response> */
    private function spaPost(string $url): TestResponse
    {
        return $this->withHeader('Referer', 'http://localhost')
            ->postJson($url);
    }

    // --- 正常系 ---

    public function test_閲覧数が1増加し200が返ること(): void
    {
        $user = User::factory()->create();
        $memo = Memo::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->spaPost("/api/memos/view-count/increase/{$memo->id}");

        $response->assertStatus(200);
        $this->assertDatabaseHas('article_statuses', ['article_id' => $memo->id, 'count' => 1]);
    }

    public function test_updated_atとedited_atが更新されないこと(): void
    {
        $user = User::factory()->create();
        $memo = Memo::factory()->create([
            'user_id' => $user->id,
            'updated_at' => now()->subMinutes(5),
            'edited_at' => now()->subMinutes(5),
        ]);
        $before = $memo->fresh();
        if ($before?->updated_at === null || $before->edited_at === null) {
            $this->fail('updated_at or edited_at was not set.');
        }

        $this->actingAs($user)->spaPost("/api/memos/view-count/increase/{$memo->id}");

        $freshMemo = $memo->fresh();
        if ($freshMemo === null || $freshMemo->updated_at === null || $freshMemo->edited_at === null) {
            $this->fail('Memo was not found after refresh.');
        }
        $this->assertTrue($freshMemo->updated_at->eq($before->updated_at));
        $this->assertTrue($freshMemo->edited_at->eq($before->edited_at));
    }

    // --- 異常系 ---

    public function test_存在しないメモの場合404が返ること(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->spaPost('/api/memos/view-count/increase/9999');

        $response->assertStatus(404);
        $response->assertJson(['messages' => ['メモが見つかりませんでした。']]);
    }

    public function test_他人のメモの場合404が返ること(): void
    {
        $owner = User::factory()->create();
        $memo = Memo::factory()->create(['user_id' => $owner->id]);
        $otherUser = User::factory()->create();

        $response = $this->actingAs($otherUser)->spaPost("/api/memos/view-count/increase/{$memo->id}");

        $response->assertStatus(404);
        $response->assertJson(['messages' => ['メモが見つかりませんでした。']]);
    }

    public function test_未認証の場合401が返ること(): void
    {
        $memo = Memo::factory()->create();

        $response = $this->spaPost("/api/memos/view-count/increase/{$memo->id}");

        $response->assertStatus(401);
    }
}
