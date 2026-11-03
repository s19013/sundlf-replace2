<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * 作成時に、同じトランザクション内でstatus行(1対1)も作成する。
 *
 * createdイベントで作成するため、`createQuietly()`・`withoutEvents()`・`DB::table()->insert()`で本体を作る場合は呼び出し側でstatus行も作成すること。
 *
 * @phpstan-require-extends Model
 *
 * @method HasOne<covariant Model, $this> status()
 */
trait HasStatusRow
{
    protected static function bootHasStatusRow(): void
    {
        static::created(function (self $model): void {
            $model->status()->create();
        });
    }
}
