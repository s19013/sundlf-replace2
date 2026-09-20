<?php

namespace App\Usecases\Memo;

use App\Exceptions\NotFoundException;
use App\Facades\Authenticated;
use App\Http\Requests\Memo\SearchMemoRequest;
use App\Http\Resources\MemoResource;
use App\Http\Resources\PaginationResource;
use App\Tools\SearchToolKit;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class SearchMemosUsecase
{
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

            // MySQL/MariaDBの文字列リテラルは `\` をエスケープ文字として解釈するため、
            // ESCAPE句に渡すバックスラッシュ自体を `\\` にエスケープする必要がある。
            $driverName = DB::connection()->getDriverName();
            $escapeClause = in_array($driverName, ['mysql', 'mariadb'], true) ? "ESCAPE '\\\\'" : "ESCAPE '\\'";

            foreach ($parsed['and'] as $word) {
                $query->where(function ($andQuery) use ($columns, $word, $escapeClause): void {
                    foreach ($columns as $column) {
                        $andQuery->orWhereRaw("{$column} LIKE ? {$escapeClause}", ["%{$word}%"]);
                    }
                });
            }

            foreach ($parsed['not'] as $word) {
                foreach ($columns as $column) {
                    $query->whereRaw("{$column} NOT LIKE ? {$escapeClause}", ["%{$word}%"]);
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
        $page = $request->integer('page', 1);

        if ($sort === 'random') {
            $query->inRandomOrder();
        } else {
            // updated_at等は同値になりやすく、順序が不定だとページ間で重複・欠落するためidを第2キーにする
            $query->orderBy($sort, 'desc')->orderBy('id', 'desc');
        }

        $memos = $query->with('tags')->paginate($itemNumber, page: $page);

        // 検索結果が0件の場合と、存在しないページを指定された場合のどちらも404
        if ($memos->isEmpty()) {
            throw new NotFoundException;
        }

        return response()->json([
            'memos' => MemoResource::collection($memos->getCollection()),
            'pagination' => new PaginationResource($memos),
        ]);
    }
}
