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
 */
trait HasStatusRow
{
    /**
     * ユーザーが直接変更しない項目(閲覧数・使用回数・タグもちフラグ)を管理するstatus行。
     *
     * @return HasOne<covariant Model, $this>
     */
    abstract public function status(): HasOne;

    protected static function bootHasStatusRow(): void
    {
        static::created(function (self $model): void {
            $model->status()->create();
        });
    }
}
