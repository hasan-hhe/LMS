<?php

use App\Http\Controllers\Dashboard\Auth\AuthController;
use App\Http\Controllers\Dashboard\AuthorController;
use App\Http\Controllers\Dashboard\BookController;
use App\Http\Controllers\Dashboard\BookInstanceController;
use App\Http\Controllers\Dashboard\BorrowingController;
use App\Http\Controllers\Dashboard\CategoryController;
use App\Http\Controllers\Dashboard\DigitalAssetController;
use App\Http\Controllers\Dashboard\FavoriteController;
use App\Http\Controllers\Dashboard\FineController;
use App\Http\Controllers\Dashboard\LibrarianController;
use App\Http\Controllers\Dashboard\MemberController;
use App\Http\Controllers\Dashboard\NotificationController;
use App\Http\Controllers\Dashboard\OrderController;
use App\Http\Controllers\Dashboard\PointController;
use App\Http\Controllers\Dashboard\PublisherController;
use App\Http\Controllers\Dashboard\ReportController;
use App\Http\Controllers\Dashboard\ReservationController;
use App\Http\Controllers\Dashboard\ReviewController;
use App\Http\Controllers\Dashboard\SaleStockController;
use App\Http\Controllers\Dashboard\TopUpCodeController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
*/

Route::prefix('v1/admin/auth')->name('dashboard.auth.')->group(function () {
    Route::post('/register', [AuthController::class, 'register'])->name('register');
    Route::post('/login', [AuthController::class, 'login'])->name('login');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
        Route::post('/me', [AuthController::class, 'me'])->name('me');
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
        Route::get('book-instances/grouped', [BookInstanceController::class, 'grouped']);
        Route::apiResource('book-instances', BookInstanceController::class);
        Route::put('book-instances/{id}/restore', [BookInstanceController::class, 'restore']);
        Route::post('sale-stock', [SaleStockController::class, 'store']);
        Route::apiResource('authors', AuthorController::class);
        Route::apiResource('categories', CategoryController::class);
        Route::apiResource('publishers', PublisherController::class);
        Route::get('digital-assets', [DigitalAssetController::class, 'index']);
        Route::post('digital-assets', [DigitalAssetController::class, 'store']);
        Route::get('books/{isbn}/digital', [DigitalAssetController::class, 'show']);
        Route::match(['post', 'put'], 'books/{isbn}/digital', [DigitalAssetController::class, 'upsert']);
        Route::delete('books/{isbn}/digital', [DigitalAssetController::class, 'destroy']);
        Route::get('notifications', [NotificationController::class, 'index']);
        Route::post('notifications/send', [NotificationController::class, 'send']);
        Route::get('favorites', [FavoriteController::class, 'index']);
        Route::get('reviews', [ReviewController::class, 'index']);
        Route::get('books/{isbn}/reviews', [ReviewController::class, 'byBook']);
        Route::delete('reviews/{review}', [ReviewController::class, 'destroy']);
        Route::get('points/balance', [PointController::class, 'balance']);
        Route::get('points/history', [PointController::class, 'history']);
        Route::post('points/top-up', [TopUpCodeController::class, 'redeem']);
        Route::get('top-up-codes', [TopUpCodeController::class, 'index']);
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
    | Librarians — Admin Only
    |--------------------------------------------------------------------------
    */
    Route::middleware('role:ADMIN,LIBRARIAN')->group(function () {
        Route::get('points/settings', [PointController::class, 'settings']);
        Route::get('order-states', [OrderController::class, 'states']);
    });

    Route::middleware('role:ADMIN')->group(function () {
        Route::apiResource('librarians', LibrarianController::class);
        Route::put('points/settings', [PointController::class, 'updateSettings']);
        Route::post('points/adjust', [PointController::class, 'adjust']);
        Route::post('top-up-codes/generate', [TopUpCodeController::class, 'generate']);
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
        Route::get('borrowings/{id}/extension-quote', [BorrowingController::class, 'quoteExtension']);
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
        Route::put('reservations/{id}/ready', [ReservationController::class, 'markReady']);
        Route::put('reservations/{id}/fulfill', [ReservationController::class, 'fulfill']);
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
        Route::get('points-summary', [ReportController::class, 'pointsSummary']);
        Route::get('points-export', [ReportController::class, 'pointsExport']);
        Route::get('fines-export', [ReportController::class, 'finesExport']);
        Route::get('overdue-export', [ReportController::class, 'overdueExport']);
    });
});
