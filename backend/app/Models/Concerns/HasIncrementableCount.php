<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Model;

/**
 * @phpstan-require-extends Model
 *
 * @property int $count
 *
 * @method static mixed withoutTimestamps(callable $callback)
 */
trait HasIncrementableCount
{
    /**
     * count を +1 する。
     * 検索への影響を避けるため updated_at は更新しない。
     */
    public function countIncrease(): void
    {
        static::withoutTimestamps(fn () => $this->increment('count'));
    }
}
