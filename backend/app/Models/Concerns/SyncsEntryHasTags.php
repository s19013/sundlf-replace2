<?php

namespace App\Models\Concerns;

use App\Models\Entry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * エントリーとタグの中間モデルに付け、紐付けの追加・削除に合わせて親エントリーの`has_tags`を同期する。
 *
 * リレーションに`using()`でこの中間モデルを指定していれば、attach/detach/sync/toggleのどれでもイベントが発火する。
 * ON DELETE CASCADEや生クエリによる中間テーブルの変更ではイベントが発火しないため、呼び出し側で同期すること。
 * タグの付け外しはエントリーの編集とは限らないため、`toBase()`で`updated_at`を更新せずに書き込む。
 *
 * @phpstan-require-extends Pivot
 */
trait SyncsEntryHasTags
{
    /**
     * 親エントリー1件に絞り込んだクエリを返す。ゴミ箱内のエントリーも対象にする。
     *
     * @return Builder<covariant Entry>
     */
    abstract protected function parentEntryQuery(): Builder;

    protected static function bootSyncsEntryHasTags(): void
    {
        static::created(function (self $pivot): void {
            $pivot->parentEntryQuery()
                ->where('has_tags', false)
                ->toBase()
                ->update(['has_tags' => true]);
        });

        static::deleted(function (self $pivot): void {
            $pivot->parentEntryQuery()
                ->whereDoesntHave('tags')
                ->toBase()
                ->update(['has_tags' => false]);
        });
    }
}
