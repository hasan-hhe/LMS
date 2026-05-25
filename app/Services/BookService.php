<?php

namespace App\Services;

use App\Models\Book;
use App\Repositories\Interfaces\BookRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class BookService
{
    public function __construct(private BookRepositoryInterface $bookRepository) {}

    public function listBooks(array $filters): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        try {
            return $this->bookRepository->getAllPaginated($filters);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function getBook(string $isbn): Book
    {
        try {
            $book = $this->bookRepository->findByIsbn($isbn);
            if (!$book) {
                throw new \Exception('الكتاب غير موجود');
            }
            return $book;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function createBook(array $data, $coverFile = null): Book
    {
        DB::beginTransaction();
        try {
            $data['cover_url'] = $this->storeCoverImage($coverFile);
            $data['rate_avg']  = $data['rate_avg'] ?? 0;

            $book = $this->bookRepository->create($data);

            DB::commit();
            return $book->load(['author', 'category', 'publisher']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function updateBook(string $isbn, array $data, $coverFile = null): Book
    {
        DB::beginTransaction();
        try {
            $book = $this->bookRepository->findByIsbn($isbn);
            if (!$book) {
                throw new \Exception('الكتاب غير موجود');
            }

            if ($coverFile) {
                $this->deleteOldCover($book->cover_url);
                $data['cover_url'] = $this->storeCoverImage($coverFile);
            }

            $updated = $this->bookRepository->update($book, $data);

            DB::commit();
            return $updated;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function deleteBook(string $isbn): void
    {
        DB::beginTransaction();
        try {
            $book = $this->bookRepository->findByIsbn($isbn);
            if (!$book) {
                throw new \Exception('الكتاب غير موجود');
            }

            $this->deleteOldCover($book->cover_url);
            $this->bookRepository->delete($book);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    private function storeCoverImage($coverFile): ?string
    {
        if (!$coverFile) {
            return null;
        }
        return Storage::disk('public')->putFile('covers', $coverFile);
    }

    private function deleteOldCover(?string $coverUrl): void
    {
        if ($coverUrl && Storage::disk('public')->exists($coverUrl)) {
            Storage::disk('public')->delete($coverUrl);
        }
    }
}
