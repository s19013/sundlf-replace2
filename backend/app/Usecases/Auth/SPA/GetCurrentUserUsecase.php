<?php

namespace App\Usecases\Auth\SPA;

use App\Exceptions\UnauthenticatedException;
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
            throw new UnauthenticatedException;
        }

        /** @var User $user */
        return response()->json([
            'user' => new MinimumUserResource($user),
        ]);
    }
}
