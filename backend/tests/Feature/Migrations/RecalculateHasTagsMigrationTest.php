<?php

namespace Tests\Feature\Migrations;

use App\Models\BookMark;
use App\Models\Entry;
use App\Models\Memo;
use App\Models\Tag;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecalculateHasTagsMigrationTest extends TestCase
{
    use RefreshDatabase;

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
