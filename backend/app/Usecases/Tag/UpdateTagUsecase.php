<?php

namespace App\Usecases\Tag;

use App\Facades\Authenticated;
use App\Http\Requests\Tag\UpdateTagRequest;
use App\Models\Tag;
use App\Usecases\Concerns\AssertOwner;
use App\Usecases\Concerns\EnsureNotExists;
use App\Usecases\Concerns\FindsModelOrFail;
use Illuminate\Http\JsonResponse;

class UpdateTagUsecase
{
    use AssertOwner;
    use EnsureNotExists;
    use FindsModelOrFail;

    public function __invoke(UpdateTagRequest $request): JsonResponse
    {
        $user = Authenticated::user();

        $id = (int) $request->validated('id');
        $newName = $request->string('name')->toString();

        $tag = $this->findOrFail(Tag::class, $id, '更新に失敗しました。');

        $this->assertOwner($tag, $user->id, 'このタグは更新できません。');

        $this->ensureTagNotExists($user, $newName, $id);

        $oldName = $tag->name;

        $tag->update(['name' => $newName]);

        return response()->json([
            'messages' => ["{$oldName}を{$newName}に更新しました。"],
        ]);
    }
}
