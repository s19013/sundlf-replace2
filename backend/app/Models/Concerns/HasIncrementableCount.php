<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @phpstan-require-extends Model
 *
 * @method HasOne<covariant Model, $this> status()
 */
trait HasIncrementableCount
{
    /**
     * 閲覧数(status行のcount)を +1 する。
     * status行は本体とは別テーブルなので、本体の updated_at は変わらない(検索への影響を避けるため)。
     */
    public function countIncrease(): void
    {
        $this->status()->increment('count');
    }
}
