<?php

namespace App\Usecases\Tag;

use App\Facades\Authenticated;
use App\Http\Resources\TagResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GetAllTagsUsecase
{
    public function __invoke(Request $request): JsonResponse
    {
        $user = Authenticated::user();

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
