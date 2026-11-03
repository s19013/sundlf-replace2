<?php

namespace App\Usecases\Memo\Concerns;

use App\Models\Memo;
use App\Models\TagStatus;
use App\Models\User;

trait SyncsMemoTags
{
    /**
     * ログインユーザーが所有するタグに絞り込んだ上でメモへの紐付けを同期し、
     * 新しく付与されたタグは使用回数(`tag_statuses.count`)をincrease、外されたタグはdecreaseする。
     * `has_tags`(`article_statuses`)は中間モデル(MemoTag)のイベントで同期される。
     * タグの付け替えはユーザーによる編集とみなすため、戻り値で増減の有無を返し、呼び出し側で`updated_at`を更新する。
     *
     * @param  array<int, int>|null  $tagIds
     * @return bool タグの増減があったかどうか
     */
    private function syncTags(Memo $memo, User $user, ?array $tagIds): bool
    {
        $validTagIds = $user->tags()
            ->whereIn('id', $tagIds ?? [])
            ->pluck('id');

        $beforeTagIds = $memo->tags()->pluck('tags.id');

        $memo->tags()->sync($validTagIds);

        $addedTagIds = $validTagIds->diff($beforeTagIds);
        $removedTagIds = $beforeTagIds->diff($validTagIds);

        if ($addedTagIds->isNotEmpty()) {
            TagStatus::whereIn('tag_id', $addedTagIds)->increment('count');
        }

        if ($removedTagIds->isNotEmpty()) {
            TagStatus::whereIn('tag_id', $removedTagIds)->decrement('count');
        }

        return $addedTagIds->isNotEmpty() || $removedTagIds->isNotEmpty();
    }
}
