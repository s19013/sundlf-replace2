<?php

namespace App\Usecases\Memo;

use App\Facades\Authenticated;
use App\Http\Requests\Memo\IncreaseMemoViewCountRequest;
use App\Models\Memo;
use App\Usecases\Concerns\AssertOwner;
use App\Usecases\Concerns\FindsModelOrFail;
use Illuminate\Http\JsonResponse;

class IncreaseMemoViewCountUsecase
{
    use AssertOwner;
    use FindsModelOrFail;

    /** Increment the view count of an owned memo. */
    public function __invoke(IncreaseMemoViewCountRequest $request): JsonResponse
    {
        $user = Authenticated::user();

        $id = (int) $request->validated('id');
        $memo = $this->findOrFail(Memo::class, $id, 'メモが見つかりませんでした。');

        $this->assertOwner($memo, $user->id, 'このメモは更新できません。');

        $memo->countIncrease();

        return response()->json();
    }
}
