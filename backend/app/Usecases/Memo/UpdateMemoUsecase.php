<?php

namespace App\Usecases\Memo;

use App\Exceptions\StaleDataException;
use App\Facades\Authenticated;
use App\Http\Requests\Memo\UpdateMemoRequest;
use App\Http\Resources\MemoResource;
use App\Models\Memo;
use App\Usecases\Concerns\AssertOwner;
use App\Usecases\Concerns\FindsModelOrFail;
use App\Usecases\Memo\Concerns\SyncsMemoTags;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class UpdateMemoUsecase
{
    use AssertOwner;
    use FindsModelOrFail;
    use SyncsMemoTags;

    public function __invoke(UpdateMemoRequest $request): JsonResponse
    {
        $user = Authenticated::user();

        $id = (int) $request->validated('id');
        $memo = $this->findOrFail(Memo::class, $id, 'メモが見つかりませんでした。');

        $this->assertOwner($memo, $user->id, 'このメモは更新できません。');

        if ($request->filled('fetched_at') && $memo->hasBeenUpdatedSinceRetrieval($request->string('fetched_at')->toString())) {
            $memo->load('tags');

            throw new StaleDataException(
                ['保存できませんでした。', '他の画面で記事が更新されています。', '競合状態を修正してください'],
                ['saved' => new MemoResource($memo)]
            );
        }

        DB::transaction(function () use ($request, $memo, $user): void {
            $memo->fill(array_filter([
                'title' => $request->input('title'),
                'body' => $request->input('body'),
                'star' => $request->input('stars'),
            ], fn (mixed $value): bool => $value !== null));

            $memo->save();

            if ($request->has('tags')) {
                $this->syncTags($memo, $user, $request->input('tags'));
            }
        });

        return response()->json([
            'messages' => ['更新しました。'],
        ]);
    }
}
