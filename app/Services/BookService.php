<?php

namespace App\Services;

use App\Models\Book;
use App\Models\BookInstance;
use App\Models\InstanceState;
use App\Repositories\Interfaces\BookRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class BookService
{
    public function __construct(
        private BookRepositoryInterface $bookRepository,
        private PointService $pointService
    ) {}

    public function listBooks(array $filters): LengthAwarePaginator
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
            if (! $book) {
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
            if ($coverFile) {
                $data['cover_url'] = $this->storeCoverImage($coverFile);
            } else {
                $data['cover_url'] = $data['cover_url'] ?? '';
            }
            $data['rate_avg'] = $data['rate_avg'] ?? 0;
            $data['price_points'] ??= $this->pointService->sypToPoints((float) ($data['price'] ?? 0));
            $copiesCount = (int) ($data['copies_count'] ?? $data['amount'] ?? 0);
            unset($data['copies_count']);

            $book = $this->bookRepository->create($data);

            if ($copiesCount > 0) {
                $availableStateId = InstanceState::where('state', 'available')->value('id');
                if (! $availableStateId) {
                    throw new \Exception('حالة النسخة المتاحة غير موجودة');
                }

                $instances = array_fill(0, $copiesCount, [
                    'book_ISBN' => $book->ISBN,
                    'state_id' => $availableStateId,
                    'condition' => 'new',
                ]);
                BookInstance::insert($instances);
            }

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
            if (! $book) {
                throw new \Exception('الكتاب غير موجود');
            }

            if ($coverFile) {
                $this->deleteOldCover($book->cover_url);
                $data['cover_url'] = $this->storeCoverImage($coverFile);
            }
            if (array_key_exists('price', $data) && ! array_key_exists('price_points', $data)) {
                $data['price_points'] = $this->pointService->sypToPoints((float) $data['price']);
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
            if (! $book) {
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
        if (! $coverFile) {
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
