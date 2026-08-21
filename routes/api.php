<?php

use App\Http\Controllers\App\Auth\AuthController;
use App\Http\Controllers\App\BooksController;
use App\Http\Controllers\App\FavoriteController;
use App\Http\Controllers\App\MemberController;
use App\Http\Controllers\App\MemberDashboardController;
use App\Http\Controllers\App\MemberSelfServiceController;
use App\Http\Controllers\App\OpacController;
use App\Http\Controllers\App\RealtimeController;
use App\Http\Controllers\App\ReviewController as MemberReviewController;
use App\Http\Controllers\BorrowingController;
use App\Http\Controllers\Dashboard\ReviewController;
use App\Http\Controllers\ReservationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Authentication Routes (Public)
|--------------------------------------------------------------------------
*/

Route::prefix('v1/auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']); // DONE
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']); // DONE
        Route::get('/me', [AuthController::class, 'me']); // done
    });
});

Route::prefix('v1/opac/books')->group(function () {
    Route::get('/', [OpacController::class, 'index']);
    Route::get('/{ISBN}', [OpacController::class, 'show']);
});

/*
|--------------------------------------------------------------------------
| App Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('member')->group(function () {
        Route::get('/dashboard', [MemberDashboardController::class, 'dashboard']); // done
        Route::post('/register', [MemberController::class, 'register']); // done
        Route::get('/get-members', [MemberController::class, 'index']); // done
        Route::put('/update-member/{id}', [MemberController::class, 'updateMember']); //
        Route::post('/control-state/{id}', [MemberController::class, 'ControlAccountState']); // done
        Route::patch('/update-participe-date/{id}', [MemberController::class, 'updateParticipeDate']); //
        Route::get('/get/{id}', [MemberController::class, 'get']); // done
    });
    Route::prefix('books')->group(function () {
        Route::get('/search', [BooksController::class, 'index']);    // search
        Route::get('/{ISBN}', [BooksController::class, 'show']);
        Route::get('/{ISBN}/copies', [BooksController::class, 'copies']);  // copy states
        Route::post('/store', [BooksController::class, 'store']);   // add book
        Route::post('/update/{ISBN}', [BooksController::class, 'update']);
        Route::post('/destroy/{ISBN}', [BooksController::class, 'destroy']);
    });
    Route::prefix('borrowing')->group(function () {
        Route::post('/borrow', [BorrowingController::class, 'borrow']);
        Route::post('/return/{id}', [BorrowingController::class, 'returnBook']);
        Route::post('/my-borrowings', [BorrowingController::class, 'index']);
        Route::post('/my-borrowings/{order}', [BorrowingController::class, 'index']);
        Route::post('/extend-borrowing/{id}', [BorrowingController::class, 'extendBorrowing']);
        Route::post('/borrowings-today', [BorrowingController::class, 'borrwoingToday']);
        Route::post('/borrowings-late', [BorrowingController::class, 'borrwoingLate']);
    });
    Route::prefix('reservation')->group(function () {
        Route::get('/my-reservations', [ReservationController::class, 'index']);
        Route::post('/reserve', [ReservationController::class, 'store']);
    });
});

Route::prefix('v1/member')
    ->middleware(['auth:sanctum', 'role:MEMBER'])
    ->controller(MemberSelfServiceController::class)
    ->group(function () {
        Route::get('/points/balance', 'balance');
        Route::get('/points/history', 'pointHistory');
        Route::post('/points/top-up', 'topUp');

        Route::get('/borrowings', 'borrowings');
        Route::put('/borrowings/{id}/extend', 'extend');

        Route::get('/fines', 'fines');
        Route::put('/fines/{id}/pay', 'payFine');

        Route::get('/reservations', 'reservations');
        Route::post('/reservations', 'createReservation');
        Route::put('/reservations/{id}/cancel', 'cancelReservation');

        Route::get('/orders', 'orders');
        Route::get('/orders/{id}', 'showOrder');
        Route::post('/orders', 'createOrder');
        Route::post('/orders/{id}/pay', 'payOrder');

        Route::put('/profile', 'updateProfile');

        Route::get('/notifications', 'notifications');
        Route::post('/notifications/read-all', 'readAllNotifications');
        Route::post('/notifications/{id}/read', 'readNotification');
    });

Route::prefix('v1')
    ->middleware(['auth:sanctum', 'role:ADMIN,LIBRARIAN'])
    ->controller(MemberSelfServiceController::class)
    ->group(function () {
        Route::get('/notifications', 'notifications');
        Route::post('/notifications/read-all', 'readAllNotifications');
        Route::post('/notifications/{id}/read', 'readNotification');
    });

Route::prefix('v1')->middleware(['auth:sanctum', 'role:ADMIN,LIBRARIAN,MEMBER'])->group(function () {
    Route::get('/realtime/token', [RealtimeController::class, 'token']);
    Route::get('/books/{isbn}/reviews', [ReviewController::class, 'byBook']);
});

Route::prefix('v1/member')->middleware(['auth:sanctum', 'role:MEMBER'])->group(function () {
    Route::get('/favorites', [FavoriteController::class, 'index']);
    Route::post('/favorites', [FavoriteController::class, 'store']);
    Route::delete('/favorites/{isbn}', [FavoriteController::class, 'destroy']);

    Route::get('/reviews', [MemberReviewController::class, 'mine']);
    Route::post('/reviews', [MemberReviewController::class, 'store']);
    Route::delete('/reviews/{review}', [MemberReviewController::class, 'destroy']);
});
