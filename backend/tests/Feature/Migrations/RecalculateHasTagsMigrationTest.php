<?php

namespace Tests\Feature\Migrations;

use App\Models\BookMark;
use App\Models\Entry;
use App\Models\Memo;
use App\Models\Tag;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RecalculateHasTagsMigrationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 補正対象の本体の`has_tags`は後続のマイグレーションで削除されるため、後続のマイグレーションを新しい順に`down()`して復元してから検証する。
     * 本体へ`has_tags`を戻すマイグレーション(2026_11_04_092911以降)も先に取り消し、カラムの重複を避ける。
     * テストDB(SQLite)ではDDLもトランザクション内で実行されるので、テスト終了時に元のスキーマへ戻る。
     */
    protected function setUp(): void
    {
        parent::setUp();

        // 新しい順に取り消す
        $laterMigrationFileNames = [
            '2026_11_04_093018_fill_edited_at_and_copy_has_tags_to_articles_book_marks_and_tags_tables.php',
            '2026_11_04_092911_add_edited_at_and_has_tags_to_articles_book_marks_and_tags_tables.php',
            '2026_11_03_043655_drop_count_and_has_tags_from_articles_book_marks_and_tags_tables.php',
        ];

        foreach ($laterMigrationFileNames as $fileName) {
            $migration = require database_path("migrations/{$fileName}");
            assert($migration instanceof Migration && method_exists($migration, 'down'));
            $migration->down();
        }

        // 現在のモデルは作成時にedited_atを書き込むため、ファクトリでデータを作れるようedited_atだけを戻す
        foreach (['articles', 'book_marks', 'tags'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->timestamp('edited_at')->nullable();
            });
        }
    }

    /**
     * @return array<int, Memo|BookMark>
     */
    private function createEntries(): array
    {
        return [Memo::factory()->create(), BookMark::factory()->create()];
    }

    private function runMigration(): void
    {
        $migration = require database_path('migrations/2026_11_02_135800_recalculate_has_tags_on_articles_and_book_marks_tables.php');
        assert($migration instanceof Migration && method_exists($migration, 'up'));
        $migration->up();
    }

    /**
     * 中間モデルのイベントを通さずにhas_tagsを書き換え、補正前のずれた状態を作る。
     */
    private function setHasTags(Entry $entry, bool $hasTags): void
    {
        $entry->newQuery()->withTrashed()->whereKey($entry->getKey())->toBase()->update(['has_tags' => $hasTags]);
    }

    private function fetchHasTags(Entry $entry): bool
    {
        return (bool) $entry->newQuery()->withTrashed()->whereKey($entry->getKey())->value('has_tags');
    }

    public function test_タグが紐付いているのにhas_tagsがfalseの行をtrueにすること(): void
    {
        foreach ($this->createEntries() as $entry) {
            $tag = Tag::factory()->create(['user_id' => $entry->user_id]);
            $entry->tags()->attach($tag->id);
            $this->setHasTags($entry, false);

            $this->runMigration();

            $this->assertTrue($this->fetchHasTags($entry));
        }
    }

    public function test_タグが紐付いていないのにhas_tagsがtrueの行をfalseにすること(): void
    {
        foreach ($this->createEntries() as $entry) {
            $this->setHasTags($entry, true);

            $this->runMigration();

            $this->assertFalse($this->fetchHasTags($entry));
        }
    }

    public function test_ゴミ箱内の行も補正すること(): void
    {
        foreach ($this->createEntries() as $entry) {
            $tag = Tag::factory()->create(['user_id' => $entry->user_id]);
            $entry->tags()->attach($tag->id);
            $entry->delete();
            $this->setHasTags($entry, false);

            $this->runMigration();

            $this->assertTrue($this->fetchHasTags($entry));
        }
    }

    public function test_updated_atを変更しないこと(): void
    {
        foreach ($this->createEntries() as $entry) {
            $tag = Tag::factory()->create(['user_id' => $entry->user_id]);
            $entry->tags()->attach($tag->id);
            $this->setHasTags($entry, false);
            $updatedAt = $entry->newQuery()->whereKey($entry->getKey())->toBase()->value('updated_at');

            $this->travel(1)->minutes();
            $this->runMigration();

            $this->assertSame($updatedAt, $entry->newQuery()->whereKey($entry->getKey())->toBase()->value('updated_at'));
        }
    }
}
