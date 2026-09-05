<?php

namespace App\Usecases\Auth\SPA;

use App\Facades\Authenticated;
use App\Http\Resources\MinimumUserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GetCurrentUserUsecase
{
    public function __invoke(Request $request): JsonResponse
    {
        $user = Authenticated::user();

        return response()->json([
            'user' => new MinimumUserResource($user),
        ]);
    }
}
