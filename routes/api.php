<?php

use App\Http\Controllers\App\Auth\AuthController;
use App\Http\Controllers\App\MemberController;
use App\Http\Controllers\App\MemberDashboardController;
use App\Http\Controllers\App\BooksController;
use App\Http\Controllers\BorrowingController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Authentication Routes (Public)
|--------------------------------------------------------------------------
*/

Route::prefix('v1/auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);//DONE

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']); //DONE
        Route::get('/me', [AuthController::class, 'me']);//done
    });
});

/*
|--------------------------------------------------------------------------
| App Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('member')->group(function () {
        Route::get('/dashboard', [MemberDashboardController::class, 'dashboard']);//done
        Route::post('/register', [MemberController::class, 'register']);//done
        Route::get('/get-members', [MemberController::class, 'index']);//done
        Route::put('/update-member/{id}', [MemberController::class, 'updateMember']);// 
        Route::post('/control-state/{id}', [MemberController::class, 'ControlAccountState']);//done
        Route::patch('/update-participe-date/{id}', [MemberController::class, 'updateParticipeDate']);// 
        Route::get('/get/{id}', [MemberController::class, 'get']);//done
    });
    Route::prefix('books')->group(function () {
        Route::get('/search',                     [BooksController::class, 'index']);    // search
        Route::get('/{ISBN}',               [BooksController::class, 'show']);
        Route::get('/{ISBN}/copies',        [BooksController::class, 'copies']);  // copy states
        Route::post('/store',               [BooksController::class, 'store']);   // add book
        Route::post('/update/{ISBN}',       [BooksController::class, 'update']);
        Route::post('/destroy/{ISBN}',      [BooksController::class, 'destroy']);
        Route::post('/borrow',      [BorrowingController::class, 'borrow']);
        Route::post('/return/{id}',      [BorrowingController::class, 'returnBook']);
        Route::post('/my-borrowings',      [BorrowingController::class, 'index']);
        Route::post('/my-borrowings/{order}',      [BorrowingController::class, 'index']);
        Route::post('/extend-borrowing/{id}',      [BorrowingController::class, 'extendBorrowing']);
    });
});
