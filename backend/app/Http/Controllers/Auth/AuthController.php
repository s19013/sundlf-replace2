<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Usecases\Auth\GetCurrentUserUsecase;
use App\Usecases\Auth\LoginUsecase;
use App\Usecases\Auth\LogoutUsecase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function login(LoginRequest $request, LoginUsecase $usecase): JsonResponse
    {
        return ($usecase)($request);
    }

    public function logout(Request $request, LogoutUsecase $usecase): JsonResponse
    {
        return ($usecase)($request);
    }

    public function user(Request $request, GetCurrentUserUsecase $usecase): JsonResponse
    {
        return ($usecase)($request);
    }
}
