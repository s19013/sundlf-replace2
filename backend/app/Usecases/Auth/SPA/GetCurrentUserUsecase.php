<?php

namespace App\Usecases\Auth\SPA;

use App\Http\Resources\MinimumUserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GetCurrentUserUsecase
{
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user === null) {
            return response()->json(['message' => __('auth.unAuthenticated')], 401);
        }

        /** @var User $user */
        return response()->json([
            'user' => new MinimumUserResource($user),
        ]);
    }
}
