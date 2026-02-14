<?php

namespace App\Usecases\Auth\SPA;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LogoutUsecase
{
    public function __invoke(Request $request): JsonResponse
    {
        // SPA認証（Sanctum）は内部的には「セッションログイン」
        // ログアウトするには、そのセッションを破棄する必要がある

        // auth:sanctum 経由で来た場合、現在のguardは "sanctum" になっている
        // sanctumガードには logout() が存在しないため、
        // セッションを管理している "web" ガードを明示的に指定する
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(null, 204);
    }
}
