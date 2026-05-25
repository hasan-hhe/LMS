<?php

namespace App\Repositories;

use App\Models\Book;
use App\Repositories\Interfaces\BookRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class BookRepository implements BookRepositoryInterface
{
    public function getAllPaginated(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = Book::with(['author', 'category', 'publisher']);

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('ISBN', 'like', "%{$search}%")
                    ->orWhereHas('author', fn($q) => $q->where('firstname', 'like', "%{$search}%")
                        ->orWhere('lastname', 'like', "%{$search}%"));
            });
        }

        if (!empty($filters['category_id'])) {
            $query->where('catagory_id', $filters['category_id']);
        }

        if (!empty($filters['author_id'])) {
            $query->where('auther_id', $filters['author_id']);
        }

        if (!empty($filters['year'])) {
            $query->where('year_of_publishing', $filters['year']);
        }

        return $query->paginate($perPage);
    }

    public function findByIsbn(string $isbn): ?Book
    {
        return Book::with(['author', 'category', 'publisher', 'instances.state'])->find($isbn);
    }

    public function create(array $data): Book
    {
        return Book::create($data);
    }

    public function update(Book $book, array $data): Book
    {
        $book->update($data);
        return $book->fresh(['author', 'category', 'publisher']);
    }

    public function delete(Book $book): bool
    {
        return $book->delete();
    }
}
