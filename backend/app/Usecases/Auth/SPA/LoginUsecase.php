<?php

namespace App\Usecases\Auth\SPA;

use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use App\Resources\MinimumUserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class LoginUsecase
{
    public function __invoke(LoginRequest $request): JsonResponse
    {
        if (! Auth::attempt($request->only('email', 'password'), $request->boolean('remember'))) {
            return response()->json([
                'message' => __('auth.failed'),
            ], 401);
        }

        $request->session()->regenerate();

        /** @var User $user */
        $user = Auth::user();

        return response()->json([
            'user' => new MinimumUserResource($user),
        ]);
    }
}
