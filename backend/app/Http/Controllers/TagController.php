<?php

namespace App\Http\Controllers;

use App\Http\Requests\Tag\CreateTagRequest;
use App\Usecases\Tag\CreateTagUsecase;
use Illuminate\Http\JsonResponse;

class TagController extends Controller
{
    public function store(CreateTagRequest $request, CreateTagUsecase $usecase): JsonResponse
    {
        return $usecase($request);
    }
}
