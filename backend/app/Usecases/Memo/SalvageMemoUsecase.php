<?php

namespace App\Usecases\Memo;

use App\Exceptions\NotFoundException;
use App\Facades\Authenticated;
use App\Http\Requests\Memo\SalvageMemoRequest;
use App\Models\Memo;
use App\Usecases\Concerns\AssertOwner;
use Illuminate\Http\JsonResponse;

class SalvageMemoUsecase
{
    use AssertOwner;

    public function __invoke(SalvageMemoRequest $request): JsonResponse
    {
        $user = Authenticated::user();

        $id = (int) $request->validated('id');
        $memo = Memo::withTrashed()->find($id);

        if ($memo === null) {
            throw new NotFoundException('メモが見つかりませんでした。');
        }

        $this->assertOwner($memo, $user->id, 'このメモは復元できません。');

        $memo->salvage();

        return response()->json([
            'messages' => ["{$memo->title} を復元しました。"],
        ]);
    }
}
