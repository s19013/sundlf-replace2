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

            $this->assertSame(1, $model->count);
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
}
