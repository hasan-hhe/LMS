<?php

namespace App\Repositories\Interfaces;

use App\Models\Book;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface BookRepositoryInterface
{
    public function getAllPaginated(array $filters, int $perPage = 15): LengthAwarePaginator;
    public function findByIsbn(string $isbn): ?Book;
    public function create(array $data): Book;
    public function update(Book $book, array $data): Book;
    public function delete(Book $book): bool;
}
