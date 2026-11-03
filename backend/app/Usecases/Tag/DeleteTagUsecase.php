<?php

namespace App\Usecases\Tag;

use App\Facades\Authenticated;
use App\Http\Requests\Tag\DeleteTagRequest;
use App\Models\BookMarkStatus;
use App\Models\MemoStatus;
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
            // このタグ以外に紐付きが無いエントリー(ゴミ箱内も含む)のstatus行が対象。UPDATEで対象行がロックされるため、同時に走るメモ更新とは直列化される。
            foreach ([MemoStatus::class, BookMarkStatus::class] as $statusClass) {
                $statusClass::whereHas('tags', fn ($query) => $query->whereKey($tag->id))
                    ->whereDoesntHave('tags', fn ($query) => $query->whereKeyNot($tag->id))
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
