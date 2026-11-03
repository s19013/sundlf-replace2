<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * 作成時に、同じトランザクション内でstatus行(1対1)も作成する。
 * `statusRowAttributes()`の値(エントリーなら`user_id`)は作成時にだけ設定する。本体の`user_id`は変更しない前提で、変更してもstatus行には反映されない。
 *
 * createdイベントで作成するため、`createQuietly()`・`withoutEvents()`・`DB::table()->insert()`で本体を作る場合は呼び出し側でstatus行も作成すること。
 * その際は`statusRowAttributes()`の値も設定すること(エントリーの`user_id`はNOT NULLで、無いとINSERTが失敗する)。
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

    /**
     * status行の作成時に、本体のidの他に設定する値。
     *
     * @return array<string, mixed>
     */
    protected function statusRowAttributes(): array
    {
        return [];
    }

    protected static function bootHasStatusRow(): void
    {
        static::created(function (self $model): void {
            $model->status()->create($model->statusRowAttributes());
        });
    }
}
