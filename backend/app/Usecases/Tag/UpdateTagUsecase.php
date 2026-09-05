<?php

namespace App\Usecases\Tag;

use App\Exceptions\DuplicationException;
use App\Facades\Authenticated;
use App\Http\Requests\Tag\UpdateTagRequest;
use App\Models\Tag;
use App\Models\User;
use App\Usecases\Concerns\AssertOwner;
use App\Usecases\Concerns\FindsModelOrFail;
use Illuminate\Http\JsonResponse;

class UpdateTagUsecase
{
    use AssertOwner;
    use FindsModelOrFail;

    public function __invoke(UpdateTagRequest $request): JsonResponse
    {
        $user = Authenticated::user();

        $id = (int) $request->validated('id');
        $newName = $request->string('name')->toString();

        $tag = $this->findOrFail(Tag::class, $id, '更新に失敗しました。');

        $this->assertOwner($tag, $user->id, 'このタグは更新できません。');

        $this->ensureNotExistsExceptSelf($user, $newName, $id);

        $oldName = $tag->name;

        $tag->update(['name' => $newName]);

        return response()->json([
            'messages' => ["{$oldName}を{$newName}に更新しました。"],
        ]);
    }

    // 自分自身を除いて重複がないか
    private function ensureNotExistsExceptSelf(User $user, string $newName, int $id): void
    {
        $duplicated = $user->tags()
            ->where('name', $newName)
            ->where('id', '!=', $id) // 自分を除外するためこの条件は必要
            ->exists();

        if ($duplicated) {
            throw new DuplicationException($newName);
        }
    }
}
