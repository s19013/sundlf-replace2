<?php

namespace App\Usecases\Auth;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GetCurrentUserUsecase
{
    public function __invoke(Request $request): JsonResponse
    {
        return response()->json($request->user());
    }
}
