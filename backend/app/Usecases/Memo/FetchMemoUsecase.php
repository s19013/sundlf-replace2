<?php

namespace App\Usecases\Memo;

use App\Exceptions\StaleDataException;
use App\Facades\Authenticated;
use App\Http\Requests\Memo\FetchMemoRequest;
use App\Http\Resources\MemoResource;
use App\Models\Memo;
use App\Usecases\Concerns\AssertOwner;
use App\Usecases\Concerns\FindsModelOrFail;
use Illuminate\Http\JsonResponse;

class FetchMemoUsecase
{
    use AssertOwner;
    use FindsModelOrFail;

    /** Fetch an owned memo and detect stale client data. */
    public function __invoke(FetchMemoRequest $request): JsonResponse
    {
        $user = Authenticated::user();

        $id = (int) $request->validated('id');
        $memo = $this->findOrFail(Memo::class, $id, 'メモが見つかりませんでした。');

        $this->assertOwner($memo, $user->id, 'このメモは取得できません。');

        if ($request->filled('fetched_at') && $memo->hasBeenUpdatedSinceRetrieval($request->string('fetched_at')->toString())) {
            throw new StaleDataException(['他の画面でメモが更新されています。反映しますか?']);
        }

        $memo->load('tags');

        return response()->json([
            'memo' => new MemoResource($memo),
        ]);
    }
}
