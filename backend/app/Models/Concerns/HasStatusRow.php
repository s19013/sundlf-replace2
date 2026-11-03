<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * 作成時に、同じトランザクション内でstatus行(1対1)も作成する。
 * `statusRowAttributes()`の値(エントリーなら`user_id`)は、本体の更新時にもstatus行へ反映する。
 *
 * createdイベントで作成するため、`createQuietly()`・`withoutEvents()`・`DB::table()->insert()`で本体を作る場合は呼び出し側でstatus行も作成すること。
 * その際は`statusRowAttributes()`の値も設定すること(エントリーの`user_id`はNOT NULLで、無いとINSERTが失敗する)。
 * 同様に、`toBase()`・`DB::table()->update()`などイベントを通さずに`statusRowAttributes()`の値を変えた場合は、status行も更新すること。
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

        static::updated(function (self $model): void {
            $changedAttributes = array_intersect_key($model->statusRowAttributes(), $model->getChanges());

            if ($changedAttributes !== []) {
                $model->status()->update($changedAttributes);
            }
        });
    }
}
