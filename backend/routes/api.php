<?php

use App\Http\Controllers\Auth\SPAAuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('/spa')->name('spa.')->group(function () {
    Route::post('/login', [SPAAuthController::class, 'login'])->middleware('throttle:10,1')->name('login');
    Route::post('/logout', [SPAAuthController::class, 'logout'])->middleware('auth:sanctum')->name('logout');
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [SPAAuthController::class, 'user'])->name('auth.user');

    Route::get('/test/auth', function () {
        return response()->json(['message' => 'api auth test']);
    });
});
