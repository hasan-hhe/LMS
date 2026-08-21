<?php

namespace App\Services;

use App\Models\Book;
use App\Models\BookInstance;
use App\Models\Borrowing;
use App\Models\LateFine;
use App\Models\Order;
use App\Models\PointTransaction;
use App\Models\Reservation;
use App\Models\TopUpCode;
use App\Models\User;
use App\Models\UserPoint;
use Illuminate\Support\Collection;
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
                    'days_overdue' => $b->daysOverdue(),
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
                'ready_reservations'       => Reservation::whereHas('state', fn ($q) => $q->where('state', 'ready'))->count(),
                'new_orders'               => Order::whereHas('state', fn ($q) => $q->whereIn('state', ['pending', 'جديد']))->count(),
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

    public function getPointsSummary(): array
    {
        return [
            'total_balance_all_users' => (int) UserPoint::sum('balance'),
            'total_top_ups' => $this->sumPointTransactions('top_up'),
            'total_spent' => $this->sumPointTransactions('spend', true),
            'total_rewards' => $this->sumPointTransactions('reward'),
            'codes_unused' => TopUpCode::where('is_used', false)->count(),
            'codes_used' => TopUpCode::where('is_used', true)->count(),
        ];
    }

    public function getPointTransactionsForExport(array $filters): Collection
    {
        $limit = min(max((int) ($filters['limit'] ?? 1000), 1), 1000);

        return PointTransaction::with('user:id,email')
            ->when($filters['user_id'] ?? null, fn ($query, $userId) => $query->where('user_id', $userId))
            ->when($filters['type'] ?? null, fn ($query, $type) => $query->where('type', $type))
            ->when($filters['from'] ?? null, fn ($query, $from) => $query->whereDate('created_at', '>=', $from))
            ->when($filters['to'] ?? null, fn ($query, $to) => $query->whereDate('created_at', '<=', $to))
            ->latest('id')
            ->limit($limit)
            ->get();
    }

    public function getFinesForExport(int $limit = 1000): Collection
    {
        return LateFine::with(['borrowing.member:id,email'])
            ->latest('id')
            ->limit(min(max($limit, 1), 1000))
            ->get();
    }

    public function getOverdueForExport(int $limit = 1000): Collection
    {
        return Borrowing::with(['member:id,email', 'bookInstance.book'])
            ->whereNull('returned_at')
            ->where('end_date', '<', now())
            ->orderBy('end_date')
            ->limit(min(max($limit, 1), 1000))
            ->get();
    }

    private function sumPointTransactions(string $type, bool $absolute = false): int
    {
        $expression = $absolute ? 'ABS(points)' : 'points';

        return (int) PointTransaction::where('type', $type)
            ->selectRaw("COALESCE(SUM({$expression}), 0) as total")
            ->value('total');
    }
}
