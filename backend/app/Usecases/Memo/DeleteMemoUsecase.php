<?php

namespace App\Usecases\Memo;

use App\Facades\Authenticated;
use App\Http\Requests\Memo\DeleteMemoRequest;
use App\Models\Memo;
use App\Models\Tag;
use App\Usecases\Concerns\AssertOwner;
use App\Usecases\Concerns\FindsModelOrFail;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class DeleteMemoUsecase
{
    use AssertOwner;
    use FindsModelOrFail;

    public function __invoke(DeleteMemoRequest $request): JsonResponse
    {
        $user = Authenticated::user();

        $id = (int) $request->validated('id');
        $memo = $this->findOrFail(Memo::class, $id, 'メモが見つかりませんでした。');

        $this->assertOwner($memo, $user->id, 'このメモは削除できません。');

        $title = $memo->title;

        DB::transaction(function () use ($memo): void {
            $tagIds = $memo->tags()->pluck('tags.id');

            if ($tagIds->isNotEmpty()) {
                Tag::whereIn('id', $tagIds)->decrement('count');
            }

            $memo->delete();
        });

        return response()->json([
            'messages' => ["{$title} を削除しました。"],
        ]);
    }
}
