<?php

namespace App\Usecases\Auth;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GetCurrentUserUsecase
{
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user === null) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        return response()->json($user);
    }
}
