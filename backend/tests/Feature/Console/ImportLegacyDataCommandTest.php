<?php

namespace Tests\Feature\Console;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Testing\PendingCommand;
use Tests\TestCase;

class ImportLegacyDataCommandTest extends TestCase
{
    use RefreshDatabase;

    /**
     * legacy接続を、テスト用のインメモリSQLiteに差し替えて旧システムのテーブルを作る。
     * 旧システムのテーブルのうち、移行コマンドが読むカラムだけを持たせる。
     */
    protected function setUp(): void
    {
        parent::setUp();

        config(['database.connections.legacy' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => false,
        ]]);
        DB::purge('legacy');

        $schema = Schema::connection('legacy');

        $schema->create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('remember_token')->nullable();
            $table->timestamps();
            $table->timestamp('logined_at')->nullable();
        });

        foreach (['articles' => 'body', 'book_marks' => 'url'] as $tableName => $contentColumn) {
            $schema->create($tableName, function (Blueprint $table) use ($contentColumn) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('count')->default(0);
                $table->string('title');
                $table->text($contentColumn);
                $table->softDeletes();
                $table->timestamps();
            });
        }

        $schema->create('tags', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('count')->default(0);
            $table->softDeletes();
            $table->timestamps();
        });

        foreach (['article_tags' => 'article_id', 'book_mark_tags' => 'book_mark_id'] as $tableName => $parentColumn) {
            $schema->create($tableName, function (Blueprint $table) use ($parentColumn) {
                $table->unsignedBigInteger($parentColumn);
                $table->unsignedBigInteger('tag_id')->nullable();
                $table->timestamps();
            });
        }
    }

    private function runImport(): void
    {
        $command = $this->artisan('legacy:import');
        assert($command instanceof PendingCommand);
        $command->assertSuccessful()->run();
    }

    private function seedLegacyData(): void
    {
        $legacy = DB::connection('legacy');

        $legacy->table('users')->insert([
            'id' => 1, 'name' => '旧ユーザー', 'email' => 'legacy@example.com', 'password' => 'hashed',
            'created_at' => '2020-01-01 00:00:00', 'updated_at' => '2020-01-01 00:00:00',
        ]);

        $legacy->table('tags')->insert([
            ['id' => 1, 'name' => '有効なタグ', 'user_id' => 1, 'count' => 2, 'deleted_at' => null, 'created_at' => '2020-01-01 00:00:00', 'updated_at' => '2020-01-02 00:00:00'],
            ['id' => 2, 'name' => '削除済みタグ', 'user_id' => 1, 'count' => 1, 'deleted_at' => '2020-01-03 00:00:00', 'created_at' => '2020-01-01 00:00:00', 'updated_at' => '2020-01-03 00:00:00'],
        ]);

        // 1: 有効なタグあり / 2: tag_idがNULLの行だけ(旧システムでのタグなし) / 3: 削除済みタグだけ / 4: 紐付けなし
        foreach ([1, 2, 3, 4] as $id) {
            $legacy->table('articles')->insert([
                'id' => $id, 'user_id' => 1, 'count' => $id * 10, 'title' => "メモ{$id}", 'body' => "本文{$id}",
                'deleted_at' => null, 'created_at' => '2020-02-01 00:00:00', 'updated_at' => "2020-02-0{$id} 12:00:00",
            ]);
        }
        $legacy->table('article_tags')->insert([
            ['article_id' => 1, 'tag_id' => 1],
            ['article_id' => 2, 'tag_id' => null],
            ['article_id' => 3, 'tag_id' => 2],
        ]);

        foreach ([1, 2] as $id) {
            $legacy->table('book_marks')->insert([
                'id' => $id, 'user_id' => 1, 'count' => $id * 100, 'title' => "ブックマーク{$id}", 'url' => "https://example.com/{$id}",
                'deleted_at' => null, 'created_at' => '2020-03-01 00:00:00', 'updated_at' => "2020-03-0{$id} 12:00:00",
            ]);
        }
        $legacy->table('book_mark_tags')->insert([
            ['book_mark_id' => 1, 'tag_id' => 1],
        ]);
    }

    public function test_メモとブックマークの編集日時に旧データの更新日時が設定されること(): void
    {
        $this->seedLegacyData();

        $this->runImport();

        foreach ([1, 2, 3, 4] as $id) {
            $this->assertDatabaseHas('articles', ['id' => $id, 'updated_at' => "2020-02-0{$id} 12:00:00", 'edited_at' => "2020-02-0{$id} 12:00:00"]);
        }
        foreach ([1, 2] as $id) {
            $this->assertDatabaseHas('book_marks', ['id' => $id, 'updated_at' => "2020-03-0{$id} 12:00:00", 'edited_at' => "2020-03-0{$id} 12:00:00"]);
        }
    }

    public function test_メモとブックマークのhas_tagsが旧データの紐付けから本体に設定されること(): void
    {
        $this->seedLegacyData();

        $this->runImport();

        $this->assertDatabaseHas('articles', ['id' => 1, 'has_tags' => true]);
        $this->assertDatabaseHas('articles', ['id' => 2, 'has_tags' => false]);
        $this->assertDatabaseHas('articles', ['id' => 3, 'has_tags' => false]);
        $this->assertDatabaseHas('articles', ['id' => 4, 'has_tags' => false]);
        $this->assertDatabaseHas('book_marks', ['id' => 1, 'has_tags' => true]);
        $this->assertDatabaseHas('book_marks', ['id' => 2, 'has_tags' => false]);
    }

    public function test_status行には閲覧数と使用回数が取り込まれること(): void
    {
        $this->seedLegacyData();

        $this->runImport();

        foreach ([1, 2, 3, 4] as $id) {
            $this->assertDatabaseHas('article_statuses', ['article_id' => $id, 'count' => $id * 10]);
        }
        foreach ([1, 2] as $id) {
            $this->assertDatabaseHas('book_mark_statuses', ['book_mark_id' => $id, 'count' => $id * 100]);
        }
        $this->assertDatabaseHas('tag_statuses', ['tag_id' => 1, 'count' => 2]);
    }

    public function test_タグの編集日時に旧データの更新日時が設定され削除済みタグは取り込まれないこと(): void
    {
        $this->seedLegacyData();

        $this->runImport();

        $this->assertDatabaseHas('tags', ['id' => 1, 'updated_at' => '2020-01-02 00:00:00', 'edited_at' => '2020-01-02 00:00:00']);
        $this->assertDatabaseMissing('tags', ['id' => 2]);
    }

    public function test_旧データの更新日時がnullの行は作成日時を編集日時に設定し両方nullなら現在時刻を設定すること(): void
    {
        $this->seedLegacyData();
        $legacy = DB::connection('legacy');
        $legacy->table('articles')->insert([
            ['id' => 5, 'user_id' => 1, 'count' => 0, 'title' => 'メモ5', 'body' => '本文5', 'deleted_at' => null, 'created_at' => '2020-04-01 00:00:00', 'updated_at' => null],
            ['id' => 6, 'user_id' => 1, 'count' => 0, 'title' => 'メモ6', 'body' => '本文6', 'deleted_at' => null, 'created_at' => null, 'updated_at' => null],
        ]);
        $legacy->table('book_marks')->insert([
            ['id' => 3, 'user_id' => 1, 'count' => 0, 'title' => 'ブックマーク3', 'url' => 'https://example.com/3', 'deleted_at' => null, 'created_at' => '2020-04-02 00:00:00', 'updated_at' => null],
            ['id' => 4, 'user_id' => 1, 'count' => 0, 'title' => 'ブックマーク4', 'url' => 'https://example.com/4', 'deleted_at' => null, 'created_at' => null, 'updated_at' => null],
        ]);
        $legacy->table('tags')->insert([
            ['id' => 3, 'name' => 'タグ3', 'user_id' => 1, 'count' => 0, 'deleted_at' => null, 'created_at' => '2020-04-03 00:00:00', 'updated_at' => null],
            ['id' => 4, 'name' => 'タグ4', 'user_id' => 1, 'count' => 0, 'deleted_at' => null, 'created_at' => null, 'updated_at' => null],
        ]);
        $this->freezeTime();
        $now = now()->toDateTimeString();

        $this->runImport();

        $this->assertDatabaseHas('articles', ['id' => 5, 'edited_at' => '2020-04-01 00:00:00']);
        $this->assertDatabaseHas('articles', ['id' => 6, 'edited_at' => $now]);
        $this->assertDatabaseHas('book_marks', ['id' => 3, 'edited_at' => '2020-04-02 00:00:00']);
        $this->assertDatabaseHas('book_marks', ['id' => 4, 'edited_at' => $now]);
        $this->assertDatabaseHas('tags', ['id' => 3, 'edited_at' => '2020-04-03 00:00:00']);
        $this->assertDatabaseHas('tags', ['id' => 4, 'edited_at' => $now]);
    }
}
