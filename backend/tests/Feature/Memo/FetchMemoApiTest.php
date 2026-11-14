<?php

namespace Tests\Feature\Memo;

use App\Models\Memo;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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

    public function test_メモとタグのレスポンスにedited_atが含まれupdated_atは含まれないこと(): void
    {
        $user = User::factory()->create();
        $memo = Memo::factory()->create(['user_id' => $user->id, 'edited_at' => '2026-01-01 00:00:00']);
        $tag = Tag::factory()->create(['user_id' => $user->id, 'edited_at' => '2026-01-02 00:00:00']);
        $memo->tags()->attach($tag->id);

        $response = $this->actingAs($user)->spaGet("/api/memos/{$memo->id}");

        $response->assertStatus(200);
        $response->assertJsonPath('memo.edited_at', '2026-01-01T00:00:00.000000Z');
        $response->assertJsonMissingPath('memo.updated_at');
        $response->assertJsonPath('memo.tags.0.edited_at', '2026-01-02T00:00:00.000000Z');
        $response->assertJsonMissingPath('memo.tags.0.updated_at');
    }

    public function test_fetched_atが編集前であれば200が返ること(): void
    {
        $user = User::factory()->create();
        $memo = Memo::factory()->create(['user_id' => $user->id]);
        $editedAt = $memo->fresh()?->edited_at;
        if ($editedAt === null) {
            $this->fail('edited_at was not set.');
        }
        $fetchedAt = $editedAt->copy()->addMinute()->toIso8601String();

        $response = $this->actingAs($user)->spaGet("/api/memos/{$memo->id}?".http_build_query(['fetched_at' => $fetchedAt]));

        $response->assertStatus(200);
    }

    public function test_fetched_atより後の更新が編集以外だけなら200が返ること(): void
    {
        $user = User::factory()->create();
        $memo = Memo::factory()->create(['user_id' => $user->id]);
        $editedAt = $memo->fresh()?->edited_at;
        if ($editedAt === null) {
            $this->fail('edited_at was not set.');
        }
        $fetchedAt = $editedAt->copy()->addMinute()->toIso8601String();
        // 取得後に、編集以外の理由で行だけが更新された状態にする
        DB::table('articles')->where('id', $memo->id)->update(['updated_at' => $editedAt->copy()->addMinutes(5)]);

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
        $response->assertJson(['messages' => ['メモが見つかりませんでした。']]);
    }

    public function test_fetched_atより後に編集されていた場合409が返ること(): void
    {
        $user = User::factory()->create();
        $memo = Memo::factory()->create(['user_id' => $user->id]);
        $editedAt = $memo->fresh()?->edited_at;
        if ($editedAt === null) {
            $this->fail('edited_at was not set.');
        }
        $fetchedAt = $editedAt->copy()->subMinute()->toIso8601String();

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
