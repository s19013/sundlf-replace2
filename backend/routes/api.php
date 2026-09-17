<?php

use App\Http\Controllers\Auth\SPAAuthController;
use App\Http\Controllers\MemoController;
use App\Http\Controllers\TagController;
use Illuminate\Support\Facades\Route;

Route::prefix('/spa')->name('spa.')->group(function () {
    Route::post('/register', [SPAAuthController::class, 'register'])->name('register');
    Route::post('/login', [SPAAuthController::class, 'login'])->middleware('throttle:10,1')->name('login');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [SPAAuthController::class, 'logout'])->name('logout');
        Route::get('/user', [SPAAuthController::class, 'user'])->name('user');
    });
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/test/auth', function () {
        return response()->json(['message' => 'api auth test']);
    });
});

Route::middleware('auth:sanctum')->prefix('tags')->name('tags.')->group(function () {
    Route::post('/', [TagController::class, 'store'])->name('create');
    Route::patch('/{id}', [TagController::class, 'update'])->name('update');
    Route::delete('/{id}', [TagController::class, 'destroy'])->name('delete');
    Route::get('/all', [TagController::class, 'all'])->name('all');
    Route::get('/', [TagController::class, 'search'])->name('search');
});

Route::middleware('auth:sanctum')->prefix('memos')->name('memos.')->group(function () {
    Route::post('/', [MemoController::class, 'store'])->name('create');
    Route::get('/search', [MemoController::class, 'search'])->name('search');
    Route::post('/view-count/increase/{id}', [MemoController::class, 'increaseViewCount'])->name('view-count.increase');
    Route::post('/{id}/salvage', [MemoController::class, 'salvage'])->name('salvage');
    Route::delete('/{id}/completely', [MemoController::class, 'destroyCompletely'])->name('complete-deletion');
    Route::get('/{id}', [MemoController::class, 'show'])->name('fetch');
    Route::patch('/{id}', [MemoController::class, 'update'])->name('update');
    Route::delete('/{id}', [MemoController::class, 'destroy'])->name('delete');
});
