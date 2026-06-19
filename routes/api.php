<?php

use App\Http\Controllers\App\Auth\AuthController;
use App\Http\Controllers\App\MemberController;
use App\Http\Controllers\App\BooksController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Authentication Routes (Public)
|--------------------------------------------------------------------------
*/

Route::prefix('v1/auth')->group(function () {
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
        Route::post('/register', [MemberController::class, 'register']);
        Route::post('/get-members', [MemberController::class, 'index']);
        Route::put('/update-member/{id}', [MemberController::class, 'updateMember']);
        Route::post('/control-state/{id}', [MemberController::class, 'ControlAccountState']);
        Route::post('/update-participe-date/{id}', [MemberController::class, 'updateParticipeDate']);
        Route::post('/get/{id}', [MemberController::class, 'get']);
    });
    Route::prefix('books')->group(function () {
        Route::get('/search',                     [BooksController::class, 'index']);    // search
        Route::get('/{ISBN}',               [BooksController::class, 'show']);
        Route::get('/{ISBN}/copies',        [BooksController::class, 'copies']);  // copy states
        Route::post('/store',               [BooksController::class, 'store']);   // add book
        Route::post('/update/{ISBN}',       [BooksController::class, 'update']);
        Route::post('/destroy/{ISBN}',      [BooksController::class, 'destroy']);
    });
});
