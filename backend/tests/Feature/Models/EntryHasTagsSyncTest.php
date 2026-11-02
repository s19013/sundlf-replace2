<?php

namespace Tests\Feature\Models;

use App\Models\BookMark;
use App\Models\Entry;
use App\Models\Memo;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EntryHasTagsSyncTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<int, Memo|BookMark>
     */
    private function createEntries(): array
    {
        return [Memo::factory()->create(), BookMark::factory()->create()];
    }

    private function fetchHasTags(Entry $entry): bool
    {
        return (bool) $entry->newQuery()->withTrashed()->whereKey($entry->getKey())->value('has_tags');
    }

    public function test_タグを紐付けるとhas_tagsがtrueになること(): void
    {
        foreach ($this->createEntries() as $entry) {
            $tag = Tag::factory()->create(['user_id' => $entry->user_id]);

            $entry->tags()->attach($tag->id);

            $this->assertTrue($this->fetchHasTags($entry));
        }
    }

    public function test_最後のタグを外すとhas_tagsがfalseになること(): void
    {
        foreach ($this->createEntries() as $entry) {
            $tag = Tag::factory()->create(['user_id' => $entry->user_id]);
            $entry->tags()->attach($tag->id);

            $entry->tags()->detach($tag->id);

            $this->assertFalse($this->fetchHasTags($entry));
        }
    }

    public function test_他のタグが残っていればhas_tagsはtrueのままであること(): void
    {
        foreach ($this->createEntries() as $entry) {
            $tags = Tag::factory()->count(2)->create(['user_id' => $entry->user_id]);
            $entry->tags()->attach($tags->pluck('id'));

            $entry->tags()->detach($tags->first()?->id);

            $this->assertTrue($this->fetchHasTags($entry));
        }
    }

    public function test_引数なしのdetachで全て外すとhas_tagsがfalseになること(): void
    {
        foreach ($this->createEntries() as $entry) {
            $tags = Tag::factory()->count(2)->create(['user_id' => $entry->user_id]);
            $entry->tags()->attach($tags->pluck('id'));

            $entry->tags()->detach();

            $this->assertFalse($this->fetchHasTags($entry));
        }
    }

    public function test_空配列でsyncするとhas_tagsがfalseになること(): void
    {
        foreach ($this->createEntries() as $entry) {
            $tag = Tag::factory()->create(['user_id' => $entry->user_id]);
            $entry->tags()->attach($tag->id);

            $entry->tags()->sync([]);

            $this->assertFalse($this->fetchHasTags($entry));
        }
    }

    public function test_ゴミ箱内のエントリーでもhas_tagsが同期されること(): void
    {
        foreach ($this->createEntries() as $entry) {
            $tag = Tag::factory()->create(['user_id' => $entry->user_id]);
            $entry->delete();

            $entry->tags()->attach($tag->id);

            $this->assertTrue($this->fetchHasTags($entry));
        }
    }

    public function test_タグの付け外しではupdated_atが更新されないこと(): void
    {
        foreach ($this->createEntries() as $entry) {
            $tag = Tag::factory()->create(['user_id' => $entry->user_id]);
            $beforeUpdatedAt = $entry->updated_at?->toIso8601String();

            $this->travel(1)->minutes();
            $entry->tags()->attach($tag->id);
            $entry->tags()->detach($tag->id);

            $this->assertSame($beforeUpdatedAt, $entry->fresh()?->updated_at?->toIso8601String());
        }
    }
}
