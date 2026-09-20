<?php

namespace App\Usecases\Memo\Concerns;

use App\Models\Memo;
use App\Models\Tag;
use App\Models\User;

trait SyncsMemoTags
{
    /**
     * ログインユーザーが所有するタグに絞り込んだ上でメモへの紐付けを同期し、
     * 新しく付与されたタグは`count`をincrease、外されたタグはdecreaseする。
     *
     * @param  array<int, int>|null  $tagIds
     */
    private function syncTags(Memo $memo, User $user, ?array $tagIds): void
    {
        $validTagIds = $user->tags()
            ->whereIn('id', $tagIds ?? [])
            ->pluck('id');

        $beforeTagIds = $memo->tags()->pluck('tags.id');

        $memo->tags()->sync($validTagIds);

        $addedTagIds = $validTagIds->diff($beforeTagIds);
        $removedTagIds = $beforeTagIds->diff($validTagIds);

        if ($addedTagIds->isNotEmpty()) {
            Tag::whereIn('id', $addedTagIds)->increment('count');
        }

        if ($removedTagIds->isNotEmpty()) {
            Tag::whereIn('id', $removedTagIds)->decrement('count');
        }
    }
}
