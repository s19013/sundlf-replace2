<?php

namespace App\Usecases\Tag;

use App\Facades\Authenticated;
use App\Http\Requests\Tag\DeleteTagRequest;
use App\Models\Tag;
use App\Usecases\Concerns\AssertOwner;
use App\Usecases\Concerns\FindsModelOrFail;
use Illuminate\Http\JsonResponse;

class DeleteTagUsecase
{
    use AssertOwner;
    use FindsModelOrFail;

    public function __invoke(DeleteTagRequest $request): JsonResponse
    {
        $user = Authenticated::user();

        $id = (int) $request->validated('id');
        $tag = $this->findOrFail(Tag::class, $id, '削除に失敗しました。');
        $this->assertOwner($tag, $user->id, 'このタグは削除できません。');

        $name = $tag->name;
        $tag->delete();

        return response()->json([
            'messages' => ["{$name}を削除しました。"],
        ]);
    }
}
