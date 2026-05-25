<?php

namespace App\Repositories\Interfaces;

use App\Models\Borrowing;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface BorrowingRepositoryInterface
{
    public function getAllPaginated(array $filters, int $perPage = 15): LengthAwarePaginator;
    public function findById(int $id): ?Borrowing;
    public function create(array $data): Borrowing;
    public function update(Borrowing $borrowing, array $data): Borrowing;
    public function getActiveBorrowingsCount(int $memberId): int;
    public function getOverdueBorrowings(): \Illuminate\Database\Eloquent\Collection;
}
