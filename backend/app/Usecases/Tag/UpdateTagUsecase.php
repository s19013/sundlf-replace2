<?php

namespace App\Usecases\Tag;

use App\Http\Requests\Tag\UpdateTagRequest;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class UpdateTagUsecase
{
    public function __invoke(UpdateTagRequest $request): JsonResponse
    {
        $user = $request->user();

        if ($user === null) {
            return response()->json(['message' => __('auth.unAuthenticated')], 401);
        }

        /** @var User $user */
        $id = (int) $request->validated('id');
        $newName = $request->string('name')->toString();

        $duplicated = $user->tags()
            ->where('name', $newName)
            ->where('id', '!=', $id)
            ->exists();

        if ($duplicated) {
            return response()->json([
                'messages' => ["{$newName}はすでに登録されています。"],
            ], 409);
        }

        $tag = Tag::find($id);

        if ($tag === null) {
            return response()->json([
                'messages' => ['更新に失敗しました。'],
            ], 404);
        }

        if (! $tag->isOwner((string) $user->id)) {
            return response()->json([
                'messages' => ['このタグは更新できません。'],
            ], 403);
        }

        $oldName = $tag->name;
        $tag->update(['name' => $newName]);

        return response()->json([
            'messages' => ["{$oldName}を{$newName}に更新しました。"],
        ]);
    }
}
