<?php

namespace Tests\Feature\Tag;

use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class UpdateTagApiTest extends TestCase
{
    use RefreshDatabase;

    /** @param array<string, string> $data
     * @return TestResponse<\Illuminate\Http\Response>
     */
    private function spaPatch(string $url, array $data): TestResponse
    {
        return $this->withHeader('Referer', 'http://localhost')
            ->patchJson($url, $data);
    }

    // --- 正常系 ---

    public function test_正常な入力でタグが更新され200が返ること(): void
    {
        $user = User::factory()->create();
        $tag = Tag::factory()->create(['user_id' => $user->id, 'name' => '旧タグ']);

        $response = $this->actingAs($user)->spaPatch("/api/tags/{$tag->id}", ['name' => '新タグ']);

        $response->assertStatus(200);
        $response->assertJson(['messages' => ['旧タグを新タグに更新しました。']]);
    }

    public function test_更新するとtagsテーブルのnameが変わること(): void
    {
        $user = User::factory()->create();
        $tag = Tag::factory()->create(['user_id' => $user->id, 'name' => '旧タグ']);

        $this->actingAs($user)->spaPatch("/api/tags/{$tag->id}", ['name' => '新タグ']);

        $this->assertDatabaseHas('tags', ['id' => $tag->id, 'name' => '新タグ']);
    }

    public function test_名前を変えずに更新しても成功すること(): void
    {
        $user = User::factory()->create();
        $tag = Tag::factory()->create(['user_id' => $user->id, 'name' => '同じ名前']);

        $response = $this->actingAs($user)->spaPatch("/api/tags/{$tag->id}", ['name' => '同じ名前']);

        $response->assertStatus(200);
    }

    // --- 異常系 ---

    public function test_同じユーザーが同じ名前の別タグを登録済みの場合409が返ること(): void
    {
        $user = User::factory()->create();
        Tag::factory()->create(['user_id' => $user->id, 'name' => '既存タグ']);
        $tag = Tag::factory()->create(['user_id' => $user->id, 'name' => '旧タグ']);

        $response = $this->actingAs($user)->spaPatch("/api/tags/{$tag->id}", ['name' => '既存タグ']);

        $response->assertStatus(409);
        $response->assertJson(['messages' => ['既存タグはすでに登録されています。']]);
    }

    public function test_更新対象のタグが存在しない場合404が返ること(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->spaPatch('/api/tags/9999', ['name' => '新タグ']);

        $response->assertStatus(404);
        $response->assertJson(['messages' => ['更新に失敗しました。']]);
    }

    public function test_他人のタグを更新しようとすると403が返ること(): void
    {
        $owner = User::factory()->create();
        $tag = Tag::factory()->create(['user_id' => $owner->id, 'name' => '旧タグ']);
        $otherUser = User::factory()->create();

        $response = $this->actingAs($otherUser)->spaPatch("/api/tags/{$tag->id}", ['name' => '新タグ']);

        $response->assertStatus(403);
        $response->assertJson(['messages' => ['このタグは更新できません。']]);
    }

    public function test_nameが未指定の場合422が返ること(): void
    {
        $user = User::factory()->create();
        $tag = Tag::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->spaPatch("/api/tags/{$tag->id}", []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('name');
    }

    public function test_事前チェック通過後に競合更新が発生した場合も409が返ること(): void
    {
        $user = User::factory()->create();
        $tag = Tag::factory()->create(['user_id' => $user->id, 'name' => '旧タグ']);
        $conflictTag = Tag::factory()->create(['user_id' => $user->id, 'name' => '別タグ']);

        // ensureNotExistsExceptSelf()の重複チェック通過後、実際のUPDATE直前に
        // 別のタグが同名に変更された状況を模擬する
        Tag::updating(function (Tag $updating) use ($conflictTag): void {
            DB::table('tags')->where('id', $conflictTag->id)->update(['name' => $updating->name]);
        });

        $response = $this->actingAs($user)->spaPatch("/api/tags/{$tag->id}", ['name' => '競合タグ']);

        $response->assertStatus(409);
        $response->assertJson(['messages' => ['競合タグはすでに登録されています。']]);
    }

    public function test_idが数値でない場合422が返ること(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->spaPatch('/api/tags/abc', ['name' => '新タグ']);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('id');
    }

    public function test_未認証の場合401が返ること(): void
    {
        $tag = Tag::factory()->create();

        $response = $this->spaPatch("/api/tags/{$tag->id}", ['name' => '新タグ']);

        $response->assertStatus(401);
    }
}
