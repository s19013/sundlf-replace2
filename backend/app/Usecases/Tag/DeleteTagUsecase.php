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
            // 中間テーブルの行はCASCADEで消え、中間モデルのイベントが発火しないため、削除前にhas_tagsを落としておく。
            // このタグ以外に紐付きが無いエントリーが対象。UPDATEで対象行がロックされるため、同時に走るメモ更新とは直列化される。
            // タグ削除はエントリーの編集ではないため、toBase()でupdated_atを更新しない
            foreach ([Memo::class, BookMark::class] as $entryClass) {
                $entryClass::withTrashed()
                    ->whereHas('tags', fn ($query) => $query->whereKey($tag->id))
                    ->whereDoesntHave('tags', fn ($query) => $query->whereKeyNot($tag->id))
                    ->toBase()
                    ->update(['has_tags' => false]);
            }

            // ON DELETE CASCADE を使って中間テーブルのデータも削除される
            $tag->delete();
        });

        return response()->json([
            'messages' => ["{$name}を削除しました。"],
        ]);
    }
}
