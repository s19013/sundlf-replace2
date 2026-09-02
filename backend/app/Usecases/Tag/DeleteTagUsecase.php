<?php

namespace App\Usecases\Tag;

use App\Facades\Authenticated;
use App\Http\Requests\Tag\DeleteTagRequest;
use App\Models\Tag;
use Illuminate\Http\JsonResponse;

class DeleteTagUsecase
{
    public function __invoke(DeleteTagRequest $request): JsonResponse
    {
        $user = Authenticated::user();

        $id = (int) $request->validated('id');
        $tag = Tag::find($id);

        if ($tag === null) {
            return response()->json([
                'messages' => ['削除に失敗しました。'],
            ], 404);
        }

        if (! $tag->isOwner((string) $user->id)) {
            return response()->json([
                'messages' => ['このタグは削除できません。'],
            ], 403);
        }

        $name = $tag->name;
        $tag->delete();

        return response()->json([
            'messages' => ["{$name}を削除しました。"],
        ]);
    }
}
