<?php

namespace App\Usecases\Memo;

use App\Exceptions\NotFoundException;
use App\Exceptions\UnprocessableException;
use App\Facades\Authenticated;
use App\Http\Requests\Memo\SalvageMemoRequest;
use App\Models\Memo;
use App\Models\Tag;
use App\Usecases\Concerns\AssertOwner;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class SalvageMemoUsecase
{
    use AssertOwner;

    public function __invoke(SalvageMemoRequest $request): JsonResponse
    {
        $user = Authenticated::user();

        $id = (int) $request->validated('id');
        $title = DB::transaction(function () use ($id, $user): string {
            $memo = Memo::withTrashed()->lockForUpdate()->find($id);

            if ($memo === null) {
                throw new NotFoundException('メモが見つかりませんでした。');
            }

            $this->assertOwner($memo, $user->id, 'このメモは復元できません。');

            if (! $memo->trashed()) {
                throw new UnprocessableException('ゴミ箱にないメモは復元できません。');
            }

            $tagIds = $memo->tags()->pluck('tags.id');

            if ($tagIds->isNotEmpty()) {
                Tag::whereIn('id', $tagIds)->increment('count');
            }

            $memo->salvage();

            return $memo->title;
        });

        return response()->json([
            'messages' => ["{$title} を復元しました。"],
        ]);
    }
}
