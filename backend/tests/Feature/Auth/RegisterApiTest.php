<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class RegisterApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<string, string>  $overrides
     * @return array<string, string>
     */
    private function validRegisterData(array $overrides = []): array
    {
        return array_merge([
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ], $overrides);
    }

    // Sanctum SPA認証でセッションを有効にするため、Refererヘッダーを付与する
    /** @param array<string, string> $data
     * @return TestResponse<\Illuminate\Http\Response>
     */
    private function spaPost(string $url, array $data): TestResponse
    {
        return $this->withHeader('Referer', 'http://localhost')
            ->postJson($url, $data);
    }

    /** @return TestResponse<\Illuminate\Http\Response> */
    private function spaGet(string $url): TestResponse
    {
        return $this->withHeader('Referer', 'http://localhost')
            ->getJson($url);
    }

    // --- 正常系 ---

    public function test_正常な入力でユーザーが登録され200が返ること(): void
    {
        $response = $this->spaPost('/api/spa/register', $this->validRegisterData());

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'user' => ['id', 'name'],
        ]);
        $response->assertJsonPath('user.name', 'テストユーザー');
    }

    public function test_登録するとusersテーブルにレコードが作成されること(): void
    {
        $this->spaPost('/api/spa/register', $this->validRegisterData());

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'name' => 'テストユーザー',
        ]);
    }

    public function test_パスワードは平文で_d_bに保存されないこと(): void
    {
        $this->spaPost('/api/spa/register', $this->validRegisterData([
            'password' => 'plainpassword',
            'password_confirmation' => 'plainpassword',
        ]));

        $user = User::where('email', 'test@example.com')->firstOrFail();

        $this->assertNotEquals('plainpassword', $user->password);
        $this->assertTrue(password_verify('plainpassword', $user->password));
    }

    public function test_登録後にuserエンドポイントで認証済みユーザー情報が返ること(): void
    {
        $this->spaPost('/api/spa/register', $this->validRegisterData());

        $response = $this->spaGet('/api/spa/user');

        $response->assertStatus(200);
        $response->assertJsonPath('user.name', 'テストユーザー');
    }

    // --- バリデーションエラー ---

    public function test_バリデーションエラー時に422が返ること(): void
    {
        $response = $this->spaPost('/api/spa/register', [
            'name' => '',
            'email' => 'invalid-email',
            'password' => 'short',
            'password_confirmation' => 'different',
        ]);

        $response->assertStatus(422);
        $response->assertJsonStructure(['errors']);
    }

    public function test_メールアドレス重複時に422が返ること(): void
    {
        User::factory()->create(['email' => 'test@example.com']);

        $response = $this->spaPost('/api/spa/register', $this->validRegisterData());

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    }
}
