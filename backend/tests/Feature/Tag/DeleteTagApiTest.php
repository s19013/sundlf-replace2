<?php

namespace Tests\Feature\Tag;

use App\Models\BookMark;
use App\Models\Memo;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class DeleteTagApiTest extends TestCase
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
        $tag = Tag::factory()->create(['user_id' => $user->id, 'name' => '削除対象タグ']);

        $response = $this->actingAs($user)->spaDelete("/api/tags/{$tag->id}");

        $response->assertStatus(200);
        $response->assertJson(['messages' => ['削除対象タグを削除しました。']]);
    }

    public function test_削除するとtagsテーブルから物理削除されること(): void
    {
        $user = User::factory()->create();
        $tag = Tag::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)->spaDelete("/api/tags/{$tag->id}");

        $this->assertDatabaseMissing('tags', ['id' => $tag->id]);
    }

    public function test_削除すると中間テーブルの紐付けも削除されること(): void
    {
        $user = User::factory()->create();
        $tag = Tag::factory()->create(['user_id' => $user->id]);
        $memo = Memo::factory()->create(['user_id' => $user->id]);
        $bookMark = BookMark::factory()->create(['user_id' => $user->id]);
        $memo->tags()->attach($tag->id);
        $bookMark->tags()->attach($tag->id);

        $this->actingAs($user)->spaDelete("/api/tags/{$tag->id}");

        $this->assertDatabaseMissing('article_tags', ['tag_id' => $tag->id]);
        $this->assertDatabaseMissing('book_mark_tags', ['tag_id' => $tag->id]);
    }

    // --- 異常系 ---

    public function test_削除対象のタグが存在しない場合404が返ること(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->spaDelete('/api/tags/9999');

        $response->assertStatus(404);
        $response->assertJson(['messages' => ['削除に失敗しました。']]);
    }

    public function test_他人のタグを削除しようとすると403が返ること(): void
    {
        $owner = User::factory()->create();
        $tag = Tag::factory()->create(['user_id' => $owner->id]);
        $otherUser = User::factory()->create();

        $response = $this->actingAs($otherUser)->spaDelete("/api/tags/{$tag->id}");

        $response->assertStatus(403);
        $response->assertJson(['messages' => ['このタグは削除できません。']]);
        $this->assertDatabaseHas('tags', ['id' => $tag->id]);
    }

    public function test_idが数値でない場合422が返ること(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->spaDelete('/api/tags/abc');

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('id');
    }

    public function test_未認証の場合401が返ること(): void
    {
        $tag = Tag::factory()->create();

        $response = $this->spaDelete("/api/tags/{$tag->id}");

        $response->assertStatus(401);
    }
}
