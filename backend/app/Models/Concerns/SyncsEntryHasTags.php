<?php

namespace App\Models\Concerns;

use App\Models\Entry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * エントリーとタグの中間モデルに付け、紐付けの追加・削除に合わせて親エントリー(ゴミ箱内を含む)の`has_tags`を同期する。
 *
 * リレーションに`using()`でこの中間モデルを指定していれば、attach/detach/sync/toggleのどれでもイベントが発火する。
 * ON DELETE CASCADEや生クエリによる中間テーブルの変更ではイベントが発火しないため、呼び出し側で同期すること。
 * Eloquent Builderの`update()`で書くため、本体の`updated_at`は更新され、`edited_at`は変わらない(ユーザーの編集ではないため)。
 *
 * @phpstan-require-extends Pivot
 */
trait SyncsEntryHasTags
{
    /**
     * 親エントリー1件に絞り込んだクエリを返す。ゴミ箱内も同期するため、`withTrashed()`を付けること。
     *
     * @return Builder<covariant Entry>
     */
    abstract protected function parentEntryQuery(): Builder;

    protected static function bootSyncsEntryHasTags(): void
    {
        static::created(function (self $pivot): void {
            $pivot->parentEntryQuery()
                ->where('has_tags', false)
                ->update(['has_tags' => true]);
        });

        static::deleted(function (self $pivot): void {
            $pivot->parentEntryQuery()
                ->whereDoesntHave('tags')
                ->update(['has_tags' => false]);
        });
    }
}
