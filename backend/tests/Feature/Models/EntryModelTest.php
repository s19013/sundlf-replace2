<?php

namespace Tests\Feature\Models;

use App\Models\BookMark;
use App\Models\Memo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EntryModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_is_owner_returns_true_only_for_matching_user_id(): void
    {
        $memo = Memo::factory()->create();
        $bookMark = BookMark::factory()->create();

        foreach ([$memo, $bookMark] as $model) {
            $this->assertTrue($model->isOwner((string) $model->user_id));
            $this->assertFalse($model->isOwner((string) ($model->user_id + 1)));
        }
    }

    public function test_count_increase_increments_count_without_touching_updated_at(): void
    {
        $memo = Memo::factory()->create();
        $bookMark = BookMark::factory()->create();

        foreach ([$memo, $bookMark] as $model) {
            $originalUpdatedAt = $model->updated_at;
            if ($originalUpdatedAt === null) {
                $this->fail('updated_at was not set.');
            }

            $model->countIncrease();
            $model->refresh();

            $updatedAtAfterIncrease = $model->updated_at;
            if ($updatedAtAfterIncrease === null) {
                $this->fail('updated_at was not set after refresh.');
            }

            $this->assertSame(1, $model->status?->count);
            $this->assertTrue($updatedAtAfterIncrease->eq($originalUpdatedAt));
        }
    }

    public function test_has_been_updated_since_retrieval_detects_staleness(): void
    {
        $memo = Memo::factory()->create();
        $bookMark = BookMark::factory()->create();

        foreach ([$memo, $bookMark] as $model) {
            $updatedAt = $model->updated_at;
            if ($updatedAt === null) {
                $this->fail('updated_at was not set.');
            }

            $fetchedAfterLastUpdate = $updatedAt->copy()->addMinute()->toIso8601String();
            $this->assertFalse($model->hasBeenUpdatedSinceRetrieval($fetchedAfterLastUpdate));

            $fetchedBeforeLastUpdate = $updatedAt->copy()->subMinute()->toIso8601String();
            $this->assertTrue($model->hasBeenUpdatedSinceRetrieval($fetchedBeforeLastUpdate));
        }
    }

    public function test_has_been_updated_since_retrieval_returns_true_for_invalid_format(): void
    {
        $memo = Memo::factory()->create();
        $bookMark = BookMark::factory()->create();

        foreach ([$memo, $bookMark] as $model) {
            $this->assertTrue($model->hasBeenUpdatedSinceRetrieval('not-a-valid-date'));
        }
    }

    public function test_is_deadline_approaching(): void
    {
        $memo = Memo::factory()->create();
        $bookMark = BookMark::factory()->create();

        foreach ([$memo, $bookMark] as $model) {
            $this->assertFalse($model->is_deadline_approaching);

            $thresholdDays = $model::DAYS_UNTIL_PERMANENT_DELETE - $model::DEADLINE_WARNING_DAYS;

            $model->deleted_at = now()->subDays($thresholdDays - 1);
            $model->save();

            $recentlyTrashed = $model->fresh();
            if ($recentlyTrashed === null) {
                $this->fail('Model was not found after refresh.');
            }
            $this->assertFalse($recentlyTrashed->is_deadline_approaching);

            $model->deleted_at = now()->subDays($thresholdDays + 1);
            $model->save();

            $longTrashed = $model->fresh();
            if ($longTrashed === null) {
                $this->fail('Model was not found after refresh.');
            }
            $this->assertTrue($longTrashed->is_deadline_approaching);
        }
    }

    public function test_is_in_trash(): void
    {
        $memo = Memo::factory()->create();
        $bookMark = BookMark::factory()->create();

        foreach ([$memo, $bookMark] as $model) {
            $this->assertFalse($model->is_in_trash);

            $model->delete();

            $freshModel = $model->fresh();
            if ($freshModel === null) {
                $this->fail('Model was not found after refresh.');
            }
            $this->assertTrue($freshModel->is_in_trash);
        }
    }

    public function test_salvage_restores_from_trash(): void
    {
        $memo = Memo::factory()->create();
        $bookMark = BookMark::factory()->create();

        foreach ([$memo, $bookMark] as $model) {
            $model->delete();
            $this->assertTrue($model->trashed());

            $this->assertTrue($model->salvage());

            $this->assertFalse($model->trashed());

            $freshModel = $model->fresh();
            if ($freshModel === null) {
                $this->fail('Model was not found after refresh.');
            }
            $this->assertFalse($freshModel->is_in_trash);
        }
    }

    /**
     * 編集対象の属性ごとに、変更後の値を返す。
     *
     * @return array<string, array{0: Memo|BookMark, 1: array<string, int|string>}>
     */
    private function createEntriesWithEditedValues(): array
    {
        return [
            'memo-title' => [Memo::factory()->create(), ['title' => '新しいタイトル']],
            'memo-body' => [Memo::factory()->create(), ['body' => '新しい本文']],
            'memo-star' => [Memo::factory()->create(['star' => 1]), ['star' => 2]],
            'bookmark-title' => [BookMark::factory()->create(), ['title' => '新しいタイトル']],
            'bookmark-url' => [BookMark::factory()->create(), ['url' => 'https://example.com/new']],
            'bookmark-star' => [BookMark::factory()->create(['star' => 1]), ['star' => 2]],
        ];
    }

    private function fetchEditedAt(Memo|BookMark $model): ?string
    {
        return $model->fresh()?->edited_at?->toIso8601String();
    }

    public function test_作成時はedited_atがcreated_atと同じ時刻になること(): void
    {
        foreach ([Memo::factory()->create(), BookMark::factory()->create()] as $model) {
            $fresh = $model->fresh();

            $this->assertNotNull($fresh?->edited_at);
            $this->assertSame($fresh->created_at?->toIso8601String(), $fresh->edited_at->toIso8601String());
        }
    }

    public function test_編集対象の属性が変わるとedited_atが更新されること(): void
    {
        foreach ($this->createEntriesWithEditedValues() as $case => [$model, $editedValues]) {
            $beforeEditedAt = $this->fetchEditedAt($model);

            $this->travel(1)->minutes();
            $model->update($editedValues);

            $fresh = $model->fresh();
            $this->assertNotSame($beforeEditedAt, $fresh?->edited_at?->toIso8601String(), $case);
            $this->assertSame($fresh?->updated_at?->toIso8601String(), $fresh?->edited_at?->toIso8601String(), $case);
        }
    }

    public function test_変更なしで保存してもedited_atとupdated_atは変わらないこと(): void
    {
        foreach ([Memo::factory()->create(), BookMark::factory()->create()] as $model) {
            $beforeEditedAt = $this->fetchEditedAt($model);
            $beforeUpdatedAt = $model->updated_at?->toIso8601String();

            $this->travel(1)->minutes();
            $model->update(['title' => $model->title]);

            $fresh = $model->fresh();
            $this->assertSame($beforeEditedAt, $fresh?->edited_at?->toIso8601String());
            $this->assertSame($beforeUpdatedAt, $fresh?->updated_at?->toIso8601String());
        }
    }

    public function test_ゴミ箱への移動と復元ではedited_atは変わらないこと(): void
    {
        foreach ([Memo::factory()->create(), BookMark::factory()->create()] as $model) {
            $beforeEditedAt = $this->fetchEditedAt($model);

            $this->travel(1)->minutes();
            $model->delete();
            $editedAtAfterDelete = $model->fresh()?->edited_at?->toIso8601String();

            $this->travel(1)->minutes();
            $model->salvage();

            $this->assertSame($beforeEditedAt, $editedAtAfterDelete);
            $this->assertSame($beforeEditedAt, $this->fetchEditedAt($model));
        }
    }

    public function test_mark_as_editedだけでedited_atが更新されること(): void
    {
        foreach ([Memo::factory()->create(), BookMark::factory()->create()] as $model) {
            $beforeEditedAt = $this->fetchEditedAt($model);

            $this->travel(1)->minutes();
            $model->markAsEdited()->save();

            $fresh = $model->fresh();
            $this->assertNotSame($beforeEditedAt, $fresh?->edited_at?->toIso8601String());
            $this->assertSame($fresh?->updated_at?->toIso8601String(), $fresh?->edited_at?->toIso8601String());
        }
    }
}
