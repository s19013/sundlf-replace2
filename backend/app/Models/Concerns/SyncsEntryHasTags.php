<?php

namespace App\Models\Concerns;

use App\Models\BookMarkStatus;
use App\Models\MemoStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * エントリーとタグの中間モデルに付け、紐付けの追加・削除に合わせて親エントリーのstatus行の`has_tags`を同期する。
 *
 * リレーションに`using()`でこの中間モデルを指定していれば、attach/detach/sync/toggleのどれでもイベントが発火する。
 * ON DELETE CASCADEや生クエリによる中間テーブルの変更ではイベントが発火しないため、呼び出し側で同期すること。
 * status行は本体とは別テーブルなので、本体の`updated_at`は変わらない。
 *
 * @phpstan-require-extends Pivot
 */
trait SyncsEntryHasTags
{
    /**
     * 親エントリーのstatus行1件に絞り込んだクエリを返す。
     *
     * @return Builder<MemoStatus>|Builder<BookMarkStatus>
     */
    abstract protected function parentStatusQuery(): Builder;

    protected static function bootSyncsEntryHasTags(): void
    {
        static::created(function (self $pivot): void {
            $pivot->parentStatusQuery()
                ->where('has_tags', false)
                ->update(['has_tags' => true]);
        });

        static::deleted(function (self $pivot): void {
            $pivot->parentStatusQuery()
                ->whereDoesntHave('tags')
                ->update(['has_tags' => false]);
        });
    }
}
