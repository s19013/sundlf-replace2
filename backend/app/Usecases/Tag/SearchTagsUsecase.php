<?php

namespace App\Usecases\Tag;

use App\Exceptions\UnauthenticatedException;
use App\Http\Requests\Tag\SearchTagRequest;
use App\Http\Resources\TagResource;
use App\Models\User;
use App\Tools\SearchToolKit;
use Illuminate\Http\JsonResponse;

class SearchTagsUsecase
{
    public function __invoke(SearchTagRequest $request): JsonResponse
    {
        $user = $request->user();

        if ($user === null) {
            throw new UnauthenticatedException;
        }

        /** @var User $user */
        $query = $user->tags();

        if ($request->filled('keywords')) {
            $parsed = SearchToolKit::parseSearchQuery($request->string('keywords')->toString());

            foreach ($parsed['and'] as $word) {
                $query->whereRaw("name LIKE ? ESCAPE '\\'", ["%{$word}%"]);
            }

            foreach ($parsed['not'] as $word) {
                $query->whereRaw("name NOT LIKE ? ESCAPE '\\'", ["%{$word}%"]);
            }
        }

        $sort = $request->string('sort', 'updated_at')->toString();
        $itemNumber = (int) $request->input('item_number', 10);

        $tags = $query->orderBy($sort, 'desc')->limit($itemNumber)->get();

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
