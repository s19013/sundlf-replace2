<?php

namespace App\Usecases\Tag;

use App\Facades\Authenticated;
use App\Http\Requests\Tag\DeleteTagRequest;
use App\Models\BookMark;
use App\Models\Memo;
use App\Models\Tag;
use App\Usecases\Concerns\AssertOwner;
use App\Usecases\Concerns\FindsModelOrFail;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class DeleteTagUsecase
{
    use AssertOwner;
    use FindsModelOrFail;

    public function __invoke(DeleteTagRequest $request): JsonResponse
    {
        $user = Authenticated::user();

        $id = (int) $request->validated('id');
        $tag = $this->findOrFail(Tag::class, $id, 'タグが見つかりませんでした。');
        $this->assertOwner($tag, $user->id, 'タグが見つかりませんでした。');

        $name = $tag->name;

        DB::transaction(function () use ($tag): void {
            // 中間テーブルの行はCASCADEで消えるため、削除前に影響を受けるエントリーを控えておく
            $memoIds = Memo::withTrashed()->whereHas('tags', fn ($query) => $query->where('tags.id', $tag->id))->pluck('id');
            $bookMarkIds = BookMark::withTrashed()->whereHas('tags', fn ($query) => $query->where('tags.id', $tag->id))->pluck('id');

            // ON DELETE CASCADE を使って中間テーブルのデータも削除される
            $tag->delete();

            // タグ削除はエントリーの編集ではないため、toBase()でupdated_atを更新せずにhas_tagsだけを落とす
            Memo::withTrashed()->whereKey($memoIds)->whereDoesntHave('tags')->toBase()->update(['has_tags' => false]);
            BookMark::withTrashed()->whereKey($bookMarkIds)->whereDoesntHave('tags')->toBase()->update(['has_tags' => false]);
        });

        return response()->json([
            'messages' => ["{$name}を削除しました。"],
        ]);
    }
}
