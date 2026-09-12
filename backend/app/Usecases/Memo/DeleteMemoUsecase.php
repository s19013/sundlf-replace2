<?php

namespace App\Usecases\Memo;

use App\Facades\Authenticated;
use App\Http\Requests\Memo\DeleteMemoRequest;
use App\Models\Memo;
use App\Usecases\Concerns\AssertOwner;
use App\Usecases\Concerns\FindsModelOrFail;
use Illuminate\Http\JsonResponse;

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
        $memo->delete();

        return response()->json([
            'messages' => ["{$title} を削除しました。"],
        ]);
    }
}
