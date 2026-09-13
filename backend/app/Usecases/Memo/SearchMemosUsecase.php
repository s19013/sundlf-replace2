<?php

namespace App\Usecases\Memo;

use App\Exceptions\NotFoundException;
use App\Facades\Authenticated;
use App\Http\Requests\Memo\SearchMemoRequest;
use App\Http\Resources\MemoResource;
use App\Tools\SearchToolKit;
use Illuminate\Http\JsonResponse;

class SearchMemosUsecase
{
    /** Search the authenticated user's memos using the requested filters. */
    public function __invoke(SearchMemoRequest $request): JsonResponse
    {
        $user = Authenticated::user();

        $query = $user->articles();

        if ($request->boolean('is_in_trashbox')) {
            $query->onlyTrashed();
        }

        if ($request->filled('keyword')) {
            $target = $request->string('target', 'title')->toString();
            $columns = match ($target) {
                'body' => ['body'],
                'both' => ['title', 'body'],
                default => ['title'],
            };

            $parsed = SearchToolKit::parseSearchQuery($request->string('keyword')->toString());

            foreach ($parsed['and'] as $word) {
                $query->where(function ($andQuery) use ($columns, $word): void {
                    foreach ($columns as $column) {
                        $andQuery->orWhereRaw("{$column} LIKE ? ESCAPE '\\'", ["%{$word}%"]);
                    }
                });
            }

            foreach ($parsed['not'] as $word) {
                foreach ($columns as $column) {
                    $query->whereRaw("{$column} NOT LIKE ? ESCAPE '\\'", ["%{$word}%"]);
                }
            }
        }

        if ($request->boolean('is_tag_not_attached')) {
            $query->whereDoesntHave('tags');
        }

        foreach (array_map('intval', $request->array('exact_match_tags')) as $tagId) {
            $query->whereHas('tags', fn ($tagQuery) => $tagQuery->where('tags.id', $tagId));
        }

        if ($request->filled('partial_match_tags')) {
            $partialMatchTags = array_map('intval', $request->array('partial_match_tags'));
            $query->whereHas('tags', fn ($tagQuery) => $tagQuery->whereIn('tags.id', $partialMatchTags));
        }

        if ($request->filled('exclusion_match_tags')) {
            $exclusionMatchTags = array_map('intval', $request->array('exclusion_match_tags'));
            $query->whereDoesntHave('tags', fn ($tagQuery) => $tagQuery->whereIn('tags.id', $exclusionMatchTags));
        }

        if ($request->filled('stars')) {
            $query->where('star', (int) $request->input('stars'));
        }

        if ($request->filled('created_at_range_start')) {
            $query->where('created_at', '>=', $request->date('created_at_range_start'));
        }

        if ($request->filled('created_at_range_end')) {
            $query->where('created_at', '<=', $request->date('created_at_range_end'));
        }

        if ($request->filled('updated_at_range_start')) {
            $query->where('updated_at', '>=', $request->date('updated_at_range_start'));
        }

        if ($request->filled('updated_at_range_end')) {
            $query->where('updated_at', '<=', $request->date('updated_at_range_end'));
        }

        $sort = $request->string('sort', 'updated_at')->toString();
        $itemNumber = (int) $request->input('item_number', 10);

        if ($sort === 'random') {
            $query->inRandomOrder();
        } else {
            $query->orderBy($sort, 'desc');
        }

        $memos = $query->with('tags')->limit($itemNumber)->get();

        if ($memos->isEmpty()) {
            throw new NotFoundException;
        }

        return response()->json([
            'memos' => MemoResource::collection($memos),
        ]);
    }
}
