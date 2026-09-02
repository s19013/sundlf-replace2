<?php

namespace App\Usecases\Tag;

use App\Http\Requests\Tag\CreateTagRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class CreateTagUsecase
{
    public function __invoke(CreateTagRequest $request): JsonResponse
    {
        $user = $request->user();

        if ($user === null) {
            return response()->json(['message' => __('auth.unAuthenticated')], 401);
        }

        /** @var User $user */
        $name = $request->string('name')->toString();

        if ($user->tags()->where('name', $name)->exists()) {
            return response()->json([
                'messages' => ["{$name}はすでに登録されています。"],
            ], 409);
        }

        $user->tags()->create(['name' => $name]);

        return response()->json([
            'messages' => ["{$name}を登録しました。"],
        ]);
    }
}
