<?php

namespace App\Usecases\Memo;

use App\Exceptions\NotFoundException;
use App\Facades\Authenticated;
use App\Http\Requests\Memo\DeleteMemoRequest;
use App\Models\Memo;
use App\Models\Tag;
use App\Usecases\Concerns\AssertOwner;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class DeleteMemoUsecase
{
    use AssertOwner;

    public function __invoke(DeleteMemoRequest $request): JsonResponse
    {
        $user = Authenticated::user();

        $id = (int) $request->validated('id');
        $title = DB::transaction(function () use ($id, $user): string {
            $memo = Memo::withTrashed()->lockForUpdate()->find($id);

            if ($memo === null || $memo->trashed()) {
                throw new NotFoundException('メモが見つかりませんでした。');
            }

            $this->assertOwner($memo, $user->id, 'このメモは削除できません。');

            $tagIds = $memo->tags()->pluck('tags.id');

            if ($tagIds->isNotEmpty()) {
                Tag::whereIn('id', $tagIds)->decrement('count');
            }

            $memo->delete();

            return $memo->title;
        });

        return response()->json([
            'messages' => ["{$title} を削除しました。"],
        ]);
    }
}
