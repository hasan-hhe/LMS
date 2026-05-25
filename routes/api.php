<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Dashboard\AuthorController;
use App\Http\Controllers\Dashboard\BookController;
use App\Http\Controllers\Dashboard\BookInstanceController;
use App\Http\Controllers\Dashboard\BorrowingController;
use App\Http\Controllers\Dashboard\CategoryController;
use App\Http\Controllers\Dashboard\FineController;
use App\Http\Controllers\Dashboard\MemberController;
use App\Http\Controllers\Dashboard\OrderController;
use App\Http\Controllers\Dashboard\PublisherController;
use App\Http\Controllers\Dashboard\ReportController;
use App\Http\Controllers\Dashboard\ReservationController;
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
        Route::get('/me', [AuthController::class, 'me']);
    });
});

/*
|--------------------------------------------------------------------------
| Dashboard Routes (Authenticated)
|--------------------------------------------------------------------------
*/
Route::prefix('v1')->middleware('auth:sanctum')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Books — Librarian & Admin
    |--------------------------------------------------------------------------
    */
    Route::middleware('role:ADMIN,LIBRARIAN')->group(function () {
        Route::apiResource('books', BookController::class)->parameters(['books' => 'isbn']);
        Route::apiResource('book-instances', BookInstanceController::class);
        Route::apiResource('authors', AuthorController::class);
        Route::apiResource('categories', CategoryController::class);
        Route::apiResource('publishers', PublisherController::class);
    });

    /*
    |--------------------------------------------------------------------------
    | Members — Librarian & Admin
    |--------------------------------------------------------------------------
    */
    Route::middleware('role:ADMIN,LIBRARIAN')->group(function () {
        Route::apiResource('members', MemberController::class);
        Route::get('members/{member}/borrowings', [MemberController::class, 'borrowings']);
        Route::get('members/{member}/fines', [MemberController::class, 'fines']);
    });

    /*
    |--------------------------------------------------------------------------
    | Borrowings — Librarian & Admin
    |--------------------------------------------------------------------------
    */
    Route::middleware('role:ADMIN,LIBRARIAN')->group(function () {
        Route::get('borrowings', [BorrowingController::class, 'index']);
        Route::post('borrowings', [BorrowingController::class, 'store']);
        Route::get('borrowings/{id}', [BorrowingController::class, 'show']);
        Route::put('borrowings/{id}/return', [BorrowingController::class, 'returnBook']);
        Route::put('borrowings/{id}/extend', [BorrowingController::class, 'extend']);
    });

    /*
    |--------------------------------------------------------------------------
    | Fines — Librarian & Admin
    |--------------------------------------------------------------------------
    */
    Route::middleware('role:ADMIN,LIBRARIAN')->group(function () {
        Route::get('fines', [FineController::class, 'index']);
        Route::put('fines/{id}/pay', [FineController::class, 'pay']);
    });

    /*
    |--------------------------------------------------------------------------
    | Reservations — Librarian & Admin
    |--------------------------------------------------------------------------
    */
    Route::middleware('role:ADMIN,LIBRARIAN')->group(function () {
        Route::get('reservations', [ReservationController::class, 'index']);
        Route::post('reservations', [ReservationController::class, 'store']);
        Route::put('reservations/{id}/cancel', [ReservationController::class, 'cancel']);
    });

    /*
    |--------------------------------------------------------------------------
    | Orders — Admin Only
    |--------------------------------------------------------------------------
    */
    Route::middleware('role:ADMIN,LIBRARIAN')->group(function () {
        Route::get('orders', [OrderController::class, 'index']);
        Route::post('orders', [OrderController::class, 'store']);
        Route::get('orders/{id}', [OrderController::class, 'show']);
        Route::put('orders/{id}/state', [OrderController::class, 'updateState']);
    });

    /*
    |--------------------------------------------------------------------------
    | Reports — Admin & Librarian
    |--------------------------------------------------------------------------
    */
    Route::middleware('role:ADMIN,LIBRARIAN')->prefix('reports')->group(function () {
        Route::get('overdue', [ReportController::class, 'overdue']);
        Route::get('stats', [ReportController::class, 'stats']);
        Route::get('most-borrowed', [ReportController::class, 'mostBorrowed']);
        Route::get('fines-summary', [ReportController::class, 'finesSummary']);
        Route::get('inventory', [ReportController::class, 'inventory']);
    });
});
