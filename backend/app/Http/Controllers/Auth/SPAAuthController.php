<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Usecases\Auth\SPA\GetCurrentUserUsecase;
use App\Usecases\Auth\SPA\LoginUsecase;
use App\Usecases\Auth\SPA\LogoutUsecase;
use App\Usecases\Auth\SPA\RegisterUsecase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SPAAuthController extends Controller
{
    public function register(RegisterRequest $request, RegisterUsecase $usecase): JsonResponse
    {
        return $usecase($request);
    }

    public function login(LoginRequest $request, LoginUsecase $usecase): JsonResponse
    {
        return $usecase($request);
    }

    public function logout(Request $request, LogoutUsecase $usecase): JsonResponse
    {
        return $usecase($request);
    }

    public function user(Request $request, GetCurrentUserUsecase $usecase): JsonResponse
    {
        return $usecase($request);
    }
}
