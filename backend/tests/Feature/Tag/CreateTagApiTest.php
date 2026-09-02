<?php

namespace Tests\Feature\Tag;

use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class CreateTagApiTest extends TestCase
{
    use RefreshDatabase;

    /** @param array<string, string> $data
     * @return TestResponse<\Illuminate\Http\Response>
     */
    private function spaPost(string $url, array $data): TestResponse
    {
        return $this->withHeader('Referer', 'http://localhost')
            ->postJson($url, $data);
    }

    // --- 正常系 ---

    public function test_正常な入力でタグが登録され200が返ること(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->spaPost('/api/tags', ['name' => '新タグ']);

        $response->assertStatus(200);
        $response->assertJson(['messages' => ['新タグを登録しました。']]);
    }

    public function test_登録するとtagsテーブルにレコードが作成されること(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->spaPost('/api/tags', ['name' => '新タグ']);

        $this->assertDatabaseHas('tags', [
            'user_id' => $user->id,
            'name' => '新タグ',
        ]);
    }

    // --- 異常系 ---

    public function test_同じユーザーが同じ名前のタグを登録済みの場合409が返ること(): void
    {
        $user = User::factory()->create();
        Tag::factory()->create(['user_id' => $user->id, 'name' => '既存タグ']);

        $response = $this->actingAs($user)->spaPost('/api/tags', ['name' => '既存タグ']);

        $response->assertStatus(409);
        $response->assertJson(['messages' => ['既存タグはすでに登録されています。']]);
    }

    public function test_別ユーザーが同じ名前のタグを登録済みでも登録できること(): void
    {
        $otherUser = User::factory()->create();
        Tag::factory()->create(['user_id' => $otherUser->id, 'name' => '既存タグ']);

        $user = User::factory()->create();
        $response = $this->actingAs($user)->spaPost('/api/tags', ['name' => '既存タグ']);

        $response->assertStatus(200);
    }

    public function test_nameが未指定の場合422が返ること(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->spaPost('/api/tags', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('name');
    }

    public function test_未認証の場合401が返ること(): void
    {
        $response = $this->spaPost('/api/tags', ['name' => '新タグ']);

        $response->assertStatus(401);
    }
}
