<?php

namespace App\Services;

use App\Models\Book;
use App\Models\BookInstance;
use App\Models\Borrowing;
use App\Models\LateFine;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ReportService
{
    public function getOverdueBorrowings(): array
    {
        try {
            $overdue = Borrowing::with(['member', 'bookInstance.book'])
                ->whereNull('returned_at')
                ->where('end_date', '<', now())
                ->orderBy('end_date')
                ->get();

            return [
                'total'     => $overdue->count(),
                'borrowings' => $overdue->map(fn($b) => [
                    'id'          => $b->id,
                    'member'      => $b->member ? $b->member->fullName() : null,
                    'book_title'  => $b->bookInstance?->book?->title,
                    'end_date'    => $b->end_date?->toDateString(),
                    'days_overdue' => now()->diffInDays($b->end_date),
                ]),
            ];
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function getGeneralStats(): array
    {
        try {
            return [
                'total_books'              => Book::count(),
                'total_members'            => User::where('role', 'MEMBER')->count(),
                'active_borrowings'        => Borrowing::whereNull('returned_at')->count(),
                'overdue_borrowings'       => Borrowing::whereNull('returned_at')->where('end_date', '<', now())->count(),
                'total_fines_unpaid'       => LateFine::where('is_paid', false)->sum('fine'),
                'total_fines_collected'    => LateFine::where('is_paid', true)->sum('fine'),
                'new_members_this_month'   => User::where('role', 'MEMBER')
                    ->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year)
                    ->count(),
                'borrowings_this_month'    => Borrowing::whereMonth('start_date', now()->month)
                    ->whereYear('start_date', now()->year)
                    ->count(),
            ];
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function getMostBorrowedBooks(int $limit = 10): array
    {
        try {
            $books = DB::table('borrowings')
                ->join('book_instances', 'borrowings.book_instance_id', '=', 'book_instances.id')
                ->join('books', 'book_instances.book_ISBN', '=', 'books.ISBN')
                ->select('books.ISBN', 'books.title', DB::raw('COUNT(borrowings.id) as borrow_count'))
                ->groupBy('books.ISBN', 'books.title')
                ->orderByDesc('borrow_count')
                ->limit($limit)
                ->get();

            return ['books' => $books];
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function getFinesSummary(): array
    {
        try {
            return [
                'total_fines'         => LateFine::count(),
                'total_amount'        => LateFine::sum('fine'),
                'paid_amount'         => LateFine::where('is_paid', true)->sum('fine'),
                'unpaid_amount'       => LateFine::where('is_paid', false)->sum('fine'),
                'unpaid_count'        => LateFine::where('is_paid', false)->count(),
                'paid_count'          => LateFine::where('is_paid', true)->count(),
                'avg_days_late'       => LateFine::avg('days_late'),
            ];
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function getInventory(): array
    {
        try {
            $totalInstances     = BookInstance::count();
            $stateCounts        = BookInstance::with('state')
                ->get()
                ->groupBy('state.state')
                ->map->count();

            return [
                'total_books'           => Book::count(),
                'total_instances'       => $totalInstances,
                'available_instances'   => $stateCounts->get('available', 0),
                'borrowed_instances'    => $stateCounts->get('borrowed', 0),
                'reserved_instances'    => $stateCounts->get('reserved', 0),
                'damaged_instances'     => $stateCounts->get('damaged', 0),
                'lost_instances'        => $stateCounts->get('lost', 0),
                'total_members'         => User::where('role', 'MEMBER')->count(),
                'expired_memberships'   => User::where('role', 'MEMBER')
                    ->whereNotNull('participe_end_date')
                    ->where('participe_end_date', '<', now())
                    ->count(),
            ];
        } catch (\Exception $e) {
            throw $e;
        }
    }
}
