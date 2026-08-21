<?php

namespace App\Services;

use App\Models\Book;
use App\Models\Borrowing;
use App\Models\OrderItem;
use App\Models\Review;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class ReviewService
{
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        $query = Review::query()->with(['user', 'book.author'])->latest('id');

        if (! empty($filters['isbn'])) {
            $query->where('book_ISBN', $filters['isbn']);
        }
        if (! empty($filters['member_id'])) {
            $query->where('user_id', (int) $filters['member_id']);
        }
        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('comment', 'like', "%{$search}%")
                    ->orWhere('book_ISBN', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($u) => $u->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%"));
            });
        }

        return $query->paginate((int) ($filters['per_page'] ?? 15));
    }

    public function listByBook(string $isbn): Collection
    {
        if (! Book::whereKey($isbn)->exists()) {
            throw new \Exception('الكتاب غير موجود');
        }

        return Review::query()
            ->where('book_ISBN', $isbn)
            ->with('user')
            ->latest('id')
            ->get();
    }

    public function listByUser(int $userId): Collection
    {
        return Review::query()
            ->where('user_id', $userId)
            ->with(['book.author'])
            ->latest('id')
            ->get();
    }

    public function canReview(int $userId, string $isbn): bool
    {
        return ! $this->alreadyReviewed($userId, $isbn)
            && $this->completedLoanOrPurchase($userId, $isbn);
    }

    public function create(int $userId, string $isbn, int $rate, ?string $comment): Review
    {
        return DB::transaction(function () use ($userId, $isbn, $rate, $comment) {
            $book = Book::query()->lockForUpdate()->find($isbn);
            if (! $book) {
                throw new \Exception('الكتاب غير موجود');
            }

            $this->assertCanCreate($userId, $isbn);

            $review = Review::query()->create([
                'user_id' => $userId,
                'book_ISBN' => $isbn,
                'rate' => $rate,
                'comment' => $comment ?? '',
            ]);

            $this->recalculate($book);

            return $review->load(['user', 'book.author']);
        });
    }

    public function delete(?int $userId, int $reviewId): void
    {
        DB::transaction(function () use ($userId, $reviewId) {
            $review = Review::query()->lockForUpdate()->find($reviewId);
            if (! $review) {
                throw new \Exception('التقييم غير موجود');
            }
            if ($userId !== null && $review->user_id !== $userId) {
                throw new \Exception('لا يمكنك حذف تقييم مستخدم آخر');
            }

            $isbn = $review->book_ISBN;
            $review->delete();

            $book = Book::query()->lockForUpdate()->find($isbn);
            if ($book) {
                $this->recalculate($book);
            }
        });
    }

    private function assertCanCreate(int $userId, string $isbn): void
    {
        if ($this->alreadyReviewed($userId, $isbn)) {
            throw new \Exception('لقد قيّمت هذا الكتاب مسبقاً');
        }

        if (! $this->completedLoanOrPurchase($userId, $isbn)) {
            throw new \Exception('يمكن التقييم فقط بعد إعادة الكتاب من الاستعارة أو بعد شراء الكتاب');
        }
    }

    private function alreadyReviewed(int $userId, string $isbn): bool
    {
        return Review::query()
            ->where('user_id', $userId)
            ->where('book_ISBN', $isbn)
            ->exists();
    }

    private function completedLoanOrPurchase(int $userId, string $isbn): bool
    {
        $returnedBorrowing = Borrowing::query()
            ->where('member_id', $userId)
            ->whereNotNull('returned_at')
            ->whereHas('bookInstance', fn ($query) => $query->where('book_ISBN', $isbn))
            ->exists();

        if ($returnedBorrowing) {
            return true;
        }

        return OrderItem::query()
            ->where('book_ISBN', $isbn)
            ->whereHas('order', fn ($query) => $query
                ->where('user_id', $userId)
                ->whereHas('state', fn ($state) => $state->where('state', 'confirmed')))
            ->exists();
    }

    private function recalculate(Book $book): void
    {
        $book->update([
            'rate_avg' => round((float) Review::where('book_ISBN', $book->ISBN)->avg('rate'), 2),
        ]);
    }
}
