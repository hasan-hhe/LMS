<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class DashboardViewController extends Controller
{
    public function login(): View
    {
        return view('admin.auth.login');
    }

    public function dashboard(): View
    {
        return view('admin.dashboard.index');
    }

    public function booksIndex(): View
    {
        return view('admin.books.index');
    }

    public function booksCreate(): View
    {
        return view('admin.books.create');
    }

    public function booksEdit(string $isbn): View
    {
        return view('admin.books.edit', compact('isbn'));
    }

    public function booksShow(string $isbn): View
    {
        return view('admin.books.show', compact('isbn'));
    }

    public function bookInstancesIndex(): View
    {
        return view('admin.book-instances.index');
    }

    public function bookInstancesCreate(): View
    {
        return view('admin.book-instances.create');
    }

    public function bookInstancesEdit(int $id): View
    {
        return view('admin.book-instances.edit', compact('id'));
    }

    public function authorsIndex(): View
    {
        return view('admin.authors.index');
    }

    public function authorsCreate(): View
    {
        return view('admin.authors.create');
    }

    public function authorsEdit(int $id): View
    {
        return view('admin.authors.edit', compact('id'));
    }

    public function categoriesIndex(): View
    {
        return view('admin.categories.index');
    }

    public function categoriesCreate(): View
    {
        return view('admin.categories.create');
    }

    public function categoriesEdit(int $id): View
    {
        return view('admin.categories.edit', compact('id'));
    }

    public function publishersIndex(): View
    {
        return view('admin.publishers.index');
    }

    public function publishersCreate(): View
    {
        return view('admin.publishers.create');
    }

    public function publishersEdit(int $id): View
    {
        return view('admin.publishers.edit', compact('id'));
    }

    public function membersIndex(): View
    {
        return view('admin.members.index');
    }

    public function membersCreate(): View
    {
        return view('admin.members.create');
    }

    public function membersEdit(int $member): View
    {
        return view('admin.members.edit', compact('member'));
    }

    public function membersShow(int $member): View
    {
        return view('admin.members.show', compact('member'));
    }

    public function borrowingsIndex(): View
    {
        return view('admin.borrowings.index');
    }

    public function borrowingsCreate(): View
    {
        return view('admin.borrowings.create');
    }

    public function finesIndex(): View
    {
        return view('admin.fines.index');
    }

    public function reservationsIndex(): View
    {
        return view('admin.reservations.index');
    }

    public function reservationsCreate(): View
    {
        return view('admin.reservations.create');
    }

    public function ordersIndex(): View
    {
        return view('admin.orders.index');
    }

    public function ordersCreate(): View
    {
        return view('admin.orders.create');
    }

    public function ordersShow(int $id): View
    {
        return view('admin.orders.show', compact('id'));
    }

    public function reportsOverdue(): View
    {
        return view('admin.reports.overdue');
    }

    public function reportsMostBorrowed(): View
    {
        return view('admin.reports.most-borrowed');
    }

    public function reportsFinesSummary(): View
    {
        return view('admin.reports.fines-summary');
    }

    public function reportsInventory(): View
    {
        return view('admin.reports.inventory');
    }
}
