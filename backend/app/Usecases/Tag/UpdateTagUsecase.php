<?php

namespace App\Usecases\Tag;

use App\Exceptions\DuplicationException;
use App\Exceptions\ForbiddenException;
use App\Exceptions\NotFoundException;
use App\Facades\Authenticated;
use App\Http\Requests\Tag\UpdateTagRequest;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class UpdateTagUsecase
{
    public function __invoke(UpdateTagRequest $request): JsonResponse
    {
        $user = Authenticated::user();

        $id = (int) $request->validated('id');
        $newName = $request->string('name')->toString();

        $this->checkDuplication($user, $newName, $id);

        $tag = $this->fetchTag($id);

        if (! $tag->isOwner((string) $user->id)) {
            throw new ForbiddenException('このタグは更新できません。');
        }

        $oldName = $tag->name;

        $tag->update(['name' => $newName]);

        return response()->json([
            'messages' => ["{$oldName}を{$newName}に更新しました。"],
        ]);
    }

    private function checkDuplication(User $user, string $newName, int $id): void
    {
        $duplicated = $user->tags()
            ->where('name', $newName)
            ->where('id', '!=', $id)
            ->exists();

        if ($duplicated) {
            throw new DuplicationException($newName);
        }
    }

    private function fetchTag(int $id): Tag
    {
        $tag = Tag::find($id);

        if ($tag === null) {
            throw new NotFoundException('更新に失敗しました。');
        }

        return $tag;
    }
}
