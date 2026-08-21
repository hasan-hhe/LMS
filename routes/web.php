<?php

use App\Http\Controllers\SwaggerController;
use App\Http\Controllers\Web\DashboardViewController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/documentation')->group(function () {
    Route::get('/', [SwaggerController::class, 'index'])->name('api.documentation');
    Route::get('/openapi.yaml', [SwaggerController::class, 'spec'])->name('api.documentation.spec');
    Route::get('/dashboard.yaml', [SwaggerController::class, 'dashboardSpec'])->name('api.documentation.dashboard');
});

Route::get('/', function () {
    return redirect()->route('admin.login');
});

Route::get('/reset-password', function () {
    return view('auth.reset-password');
})->name('password.reset');

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/login', [DashboardViewController::class, 'login'])->name('login');

    Route::get('/', [DashboardViewController::class, 'dashboard'])->name('dashboard');

    Route::get('/books', [DashboardViewController::class, 'booksIndex'])->name('books.index');
    Route::get('/books/create', [DashboardViewController::class, 'booksCreate'])->name('books.create');
    Route::get('/books/{isbn}', [DashboardViewController::class, 'booksShow'])->name('books.show');
    Route::get('/books/{isbn}/edit', [DashboardViewController::class, 'booksEdit'])->name('books.edit');

    Route::get('/digital-assets', [DashboardViewController::class, 'digitalAssetsIndex'])->name('digital-assets.index');
    Route::get('/digital-assets/create', [DashboardViewController::class, 'digitalAssetsCreate'])->name('digital-assets.create');
    Route::get('/digital-assets/{isbn}/edit', [DashboardViewController::class, 'digitalAssetsEdit'])->name('digital-assets.edit');

    Route::get('/book-instances', [DashboardViewController::class, 'bookInstancesIndex'])->name('book-instances.index');
    Route::get('/book-instances/create', [DashboardViewController::class, 'bookInstancesCreate'])->name('book-instances.create');
    Route::get('/book-instances/{id}/edit', [DashboardViewController::class, 'bookInstancesEdit'])->name('book-instances.edit');

    Route::get('/sale-copies', [DashboardViewController::class, 'saleCopiesIndex'])->name('sale-copies.index');
    Route::get('/sale-copies/create', [DashboardViewController::class, 'saleCopiesCreate'])->name('sale-copies.create');

    Route::get('/authors', [DashboardViewController::class, 'authorsIndex'])->name('authors.index');
    Route::get('/authors/create', [DashboardViewController::class, 'authorsCreate'])->name('authors.create');
    Route::get('/authors/{id}/edit', [DashboardViewController::class, 'authorsEdit'])->name('authors.edit');

    Route::get('/categories', [DashboardViewController::class, 'categoriesIndex'])->name('categories.index');
    Route::get('/categories/create', [DashboardViewController::class, 'categoriesCreate'])->name('categories.create');
    Route::get('/categories/{id}/edit', [DashboardViewController::class, 'categoriesEdit'])->name('categories.edit');

    Route::get('/publishers', [DashboardViewController::class, 'publishersIndex'])->name('publishers.index');
    Route::get('/publishers/create', [DashboardViewController::class, 'publishersCreate'])->name('publishers.create');
    Route::get('/publishers/{id}/edit', [DashboardViewController::class, 'publishersEdit'])->name('publishers.edit');

    Route::get('/members', [DashboardViewController::class, 'membersIndex'])->name('members.index');
    Route::get('/members/create', [DashboardViewController::class, 'membersCreate'])->name('members.create');
    Route::get('/members/{member}', [DashboardViewController::class, 'membersShow'])->name('members.show');
    Route::get('/members/{member}/edit', [DashboardViewController::class, 'membersEdit'])->name('members.edit');

    Route::get('/librarians', [DashboardViewController::class, 'librariansIndex'])->name('librarians.index');
    Route::get('/librarians/create', [DashboardViewController::class, 'librariansCreate'])->name('librarians.create');
    Route::get('/librarians/{librarian}/edit', [DashboardViewController::class, 'librariansEdit'])->name('librarians.edit');

    Route::get('/borrowings', [DashboardViewController::class, 'borrowingsIndex'])->name('borrowings.index');
    Route::get('/borrowings/create', [DashboardViewController::class, 'borrowingsCreate'])->name('borrowings.create');

    Route::get('/fines', [DashboardViewController::class, 'finesIndex'])->name('fines.index');

    Route::get('/reservations', [DashboardViewController::class, 'reservationsIndex'])->name('reservations.index');
    Route::get('/reservations/create', [DashboardViewController::class, 'reservationsCreate'])->name('reservations.create');

    Route::get('/orders', [DashboardViewController::class, 'ordersIndex'])->name('orders.index');
    Route::get('/orders/create', [DashboardViewController::class, 'ordersCreate'])->name('orders.create');
    Route::get('/orders/{id}', [DashboardViewController::class, 'ordersShow'])->name('orders.show');

    Route::get('/reviews', [DashboardViewController::class, 'reviewsIndex'])->name('reviews.index');

    Route::get('/notifications', [DashboardViewController::class, 'notifications'])->name('notifications');

    Route::get('/points/top-up-codes', [DashboardViewController::class, 'pointsTopUpCodes'])->name('points.top-up-codes');
    Route::get('/points/settings', [DashboardViewController::class, 'pointsSettings'])->name('points.settings');

    Route::get('/reports/overdue', [DashboardViewController::class, 'reportsOverdue'])->name('reports.overdue');
    Route::get('/reports/most-borrowed', [DashboardViewController::class, 'reportsMostBorrowed'])->name('reports.most-borrowed');
    Route::get('/reports/fines-summary', [DashboardViewController::class, 'reportsFinesSummary'])->name('reports.fines-summary');
    Route::get('/reports/inventory', [DashboardViewController::class, 'reportsInventory'])->name('reports.inventory');
    Route::get('/reports/points', [DashboardViewController::class, 'reportsPoints'])->name('reports.points');
});
