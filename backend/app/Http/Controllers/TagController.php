<?php

namespace App\Http\Controllers;

use App\Http\Requests\Tag\CreateTagRequest;
use App\Http\Requests\Tag\DeleteTagRequest;
use App\Http\Requests\Tag\UpdateTagRequest;
use App\Usecases\Tag\CreateTagUsecase;
use App\Usecases\Tag\DeleteTagUsecase;
use App\Usecases\Tag\GetAllTagsUsecase;
use App\Usecases\Tag\UpdateTagUsecase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TagController extends Controller
{
    public function all(Request $request, GetAllTagsUsecase $usecase): JsonResponse
    {
        return $usecase($request);
    }

    public function store(CreateTagRequest $request, CreateTagUsecase $usecase): JsonResponse
    {
        return $usecase($request);
    }

    public function update(UpdateTagRequest $request, UpdateTagUsecase $usecase): JsonResponse
    {
        return $usecase($request);
    }

    public function destroy(DeleteTagRequest $request, DeleteTagUsecase $usecase): JsonResponse
    {
        return $usecase($request);
    }
}
