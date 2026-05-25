<?php

namespace App\Repositories;

use App\Models\Borrowing;
use App\Repositories\Interfaces\BorrowingRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class BorrowingRepository implements BorrowingRepositoryInterface
{
    public function getAllPaginated(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = Borrowing::with(['member', 'librarian', 'bookInstance.book', 'lateFine']);

        if (!empty($filters['member_id'])) {
            $query->where('member_id', $filters['member_id']);
        }

        if (!empty($filters['is_returned'])) {
            $filters['is_returned'] === 'true'
                ? $query->whereNotNull('returned_at')
                : $query->whereNull('returned_at');
        }

        return $query->orderByDesc('id')->paginate($perPage);
    }

    public function findById(int $id): ?Borrowing
    {
        return Borrowing::with(['member', 'librarian', 'bookInstance.book', 'lateFine', 'editions'])->find($id);
    }

    public function create(array $data): Borrowing
    {
        return Borrowing::create($data);
    }

    public function update(Borrowing $borrowing, array $data): Borrowing
    {
        $borrowing->update($data);
        return $borrowing->fresh();
    }

    public function getActiveBorrowingsCount(int $memberId): int
    {
        return Borrowing::where('member_id', $memberId)
            ->whereNull('returned_at')
            ->count();
    }

    public function getOverdueBorrowings(): Collection
    {
        return Borrowing::with(['member', 'bookInstance.book'])
            ->whereNull('returned_at')
            ->where('end_date', '<', now())
            ->get();
    }
}
