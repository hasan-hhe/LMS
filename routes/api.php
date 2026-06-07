<?php

use App\Http\Controllers\App\Auth\AuthController;
use App\Http\Controllers\App\MemberController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Authentication Routes (Public)
|--------------------------------------------------------------------------
*/

Route::prefix('v1/auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
    });
});

/*
|--------------------------------------------------------------------------
| App Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('member')->group(function () {
        Route::post('/get-members', [MemberController::class, 'index']);
        Route::post('/update-member/{id}', [MemberController::class, 'updateMember']);
        Route::post('/control-state/{id}', [MemberController::class, 'ControlAccountState']);
        Route::post('/update-participe-date/{id}', [MemberController::class, 'updateParticipeDate']);
        Route::post('/get/{id}', [MemberController::class, 'get']);
    });
});
