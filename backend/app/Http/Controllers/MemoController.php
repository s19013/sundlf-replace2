<?php

namespace App\Http\Controllers;

use App\Http\Requests\Memo\CompletelyDeleteMemoRequest;
use App\Http\Requests\Memo\CreateMemoRequest;
use App\Http\Requests\Memo\DeleteMemoRequest;
use App\Http\Requests\Memo\FetchMemoRequest;
use App\Http\Requests\Memo\IncreaseMemoViewCountRequest;
use App\Http\Requests\Memo\SalvageMemoRequest;
use App\Http\Requests\Memo\SearchMemoRequest;
use App\Http\Requests\Memo\UpdateMemoRequest;
use App\Usecases\Memo\CompletelyDeleteMemoUsecase;
use App\Usecases\Memo\CreateMemoUsecase;
use App\Usecases\Memo\DeleteMemoUsecase;
use App\Usecases\Memo\FetchMemoUsecase;
use App\Usecases\Memo\IncreaseMemoViewCountUsecase;
use App\Usecases\Memo\SalvageMemoUsecase;
use App\Usecases\Memo\SearchMemosUsecase;
use App\Usecases\Memo\UpdateMemoUsecase;
use Illuminate\Http\JsonResponse;

class MemoController extends Controller
{
    /** Create a memo. */
    public function store(CreateMemoRequest $request, CreateMemoUsecase $usecase): JsonResponse
    {
        return $usecase($request);
    }

    /** Fetch a memo. */
    public function show(FetchMemoRequest $request, FetchMemoUsecase $usecase): JsonResponse
    {
        return $usecase($request);
    }

    /** Update a memo. */
    public function update(UpdateMemoRequest $request, UpdateMemoUsecase $usecase): JsonResponse
    {
        return $usecase($request);
    }

    /** Move a memo to the trash. */
    public function destroy(DeleteMemoRequest $request, DeleteMemoUsecase $usecase): JsonResponse
    {
        return $usecase($request);
    }

    /** Permanently delete a memo from the trash. */
    public function destroyCompletely(CompletelyDeleteMemoRequest $request, CompletelyDeleteMemoUsecase $usecase): JsonResponse
    {
        return $usecase($request);
    }

    /** Restore a memo from the trash. */
    public function salvage(SalvageMemoRequest $request, SalvageMemoUsecase $usecase): JsonResponse
    {
        return $usecase($request);
    }

    /** Search the authenticated user's memos. */
    public function search(SearchMemoRequest $request, SearchMemosUsecase $usecase): JsonResponse
    {
        return $usecase($request);
    }

    /** Increment a memo's view count. */
    public function increaseViewCount(IncreaseMemoViewCountRequest $request, IncreaseMemoViewCountUsecase $usecase): JsonResponse
    {
        return $usecase($request);
    }
}
