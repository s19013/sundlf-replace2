<?php

namespace App\Usecases\Memo;

use App\Exceptions\NotFoundException;
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

        $this->assertOwner($memo, $user->id, 'メモが見つかりませんでした。');

        DB::transaction(function () use ($request, $memo, $user): void {
            /** @var Memo|null $locked */
            $locked = Memo::whereKey($memo->id)->lockForUpdate()->first();

            if ($locked === null) {
                throw new NotFoundException('メモが見つかりませんでした。');
            }

            if ($request->filled('fetched_at') && $locked->hasBeenUpdatedSinceRetrieval($request->string('fetched_at')->toString())) {
                $locked->load('tags.status');

                throw new StaleDataException(
                    ['保存できませんでした。', '他の画面で記事が更新されています。', '競合状態を修正してください'],
                    ['saved' => new MemoResource($locked)]
                );
            }

            $locked->fill(array_filter([
                'title' => $request->input('title'),
                'body' => $request->input('body'),
                'star' => $request->input('stars'),
            ], fn (mixed $value): bool => $value !== null));

            $isTagsChanged = $request->has('tags') && $this->syncTags($locked, $user, $request->input('tags'));

            // タグの付け替えも編集とみなし、updated_atの更新と変更した属性の保存を1回のUPDATEで行う
            if ($isTagsChanged) {
                $locked->touch();
            } else {
                $locked->save();
            }
        });

        return response()->json([
            'messages' => ['更新しました。'],
        ]);
    }
}
