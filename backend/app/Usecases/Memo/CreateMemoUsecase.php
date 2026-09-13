<?php

namespace App\Usecases\Memo;

use App\Facades\Authenticated;
use App\Http\Requests\Memo\CreateMemoRequest;
use App\Usecases\Memo\Concerns\SyncsMemoTags;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class CreateMemoUsecase
{
    use SyncsMemoTags;

    /** Create a memo and synchronize its tags. */
    public function __invoke(CreateMemoRequest $request): JsonResponse
    {
        $user = Authenticated::user();

        $memo = DB::transaction(function () use ($request, $user) {
            $memo = $user->articles()->create([
                'title' => $request->input('title') ?? '',
                'body' => $request->input('body') ?? '',
                'star' => $request->input('stars') ?? 0,
            ]);

            $this->syncTags($memo, $user, $request->input('tags'));

            return $memo;
        });

        return response()->json([
            'id' => $memo->id,
        ]);
    }
}
