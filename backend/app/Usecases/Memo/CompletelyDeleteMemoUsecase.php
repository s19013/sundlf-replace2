<?php

namespace App\Usecases\Memo;

use App\Exceptions\NotFoundException;
use App\Exceptions\UnprocessableException;
use App\Facades\Authenticated;
use App\Http\Requests\Memo\CompletelyDeleteMemoRequest;
use App\Models\Memo;
use App\Usecases\Concerns\AssertOwner;
use Illuminate\Http\JsonResponse;

class CompletelyDeleteMemoUsecase
{
    use AssertOwner;

    public function __invoke(CompletelyDeleteMemoRequest $request): JsonResponse
    {
        $user = Authenticated::user();

        $id = (int) $request->validated('id');
        $memo = Memo::withTrashed()->find($id);

        if ($memo === null) {
            throw new NotFoundException('メモが見つかりませんでした。');
        }

        $this->assertOwner($memo, $user->id, 'このメモは完全削除できません。');

        if (! $memo->trashed()) {
            throw new UnprocessableException('ゴミ箱にないメモは完全削除できません。');
        }

        $title = $memo->title;

        // ON DELETE CASCADE を使って中間テーブルのデータも削除される
        $memo->forceDelete();

        return response()->json([
            'messages' => ["{$title}を完全削除しました。"],
        ]);
    }
}
