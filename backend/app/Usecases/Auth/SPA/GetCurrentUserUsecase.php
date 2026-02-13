<?php

namespace App\Usecases\Auth\SPA;

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

        return response()->json([
            /** @var User $user */
            'user' => $user->setVisible(User::MINIMUM_VISIBLE)->toArray(),
        ]);
    }
}
