<?php

namespace App\Usecases\Tag;

use App\Exceptions\UnauthenticatedException;
use App\Http\Resources\TagResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GetAllTagsUsecase
{
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user === null) {
            throw new UnauthenticatedException;
        }

        /** @var User $user */
        $tags = $user->tags()->get();

        if ($tags->isEmpty()) {
            return response()->json([
                'messages' => ['見つかりませんでした。'],
            ], 404);
        }

        return response()->json([
            'tags' => TagResource::collection($tags),
        ]);
    }
}
