<?php

namespace App\Usecases\Auth\SPA;

use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\MinimumUserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class RegisterUsecase
{
    public function __invoke(RegisterRequest $request): JsonResponse
    {
        $user = User::create($request->only('name', 'email', 'password'));

        Auth::login($user);
        $request->session()->regenerate();

        return response()->json([
            'user' => new MinimumUserResource($user),
        ]);
    }
}
