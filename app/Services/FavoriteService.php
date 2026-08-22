<?php

namespace App\Services;

use App\Models\Book;
use App\Models\Favorite;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class FavoriteService
{
    public function list(int $userId): Collection
    {
        return Favorite::query()
            ->where('user_id', $userId)
            ->with([
                'book' => fn ($query) => $query
                    ->with(['author', 'category', 'publisher', 'digitalAsset'])
                    ->withCount([
                        'instances',
                        'instances as available_count' => fn ($instances) => $instances
                            ->whereHas('state', fn ($state) => $state->where('state', 'available')),
                    ]),
            ])
            ->latest()
            ->get();
    }

    public function add(int $userId, string $isbn): Favorite
    {
        return DB::transaction(function () use ($userId, $isbn) {
            if (! Book::whereKey($isbn)->exists()) {
                throw new \Exception('الكتاب غير موجود');
            }

            return Favorite::firstOrCreate([
                'user_id' => $userId,
                'book_ISBN' => $isbn,
            ])->load(['book.author', 'book.category', 'book.publisher']);
        });
    }

    public function remove(int $userId, string $isbn): void
    {
        DB::transaction(function () use ($userId, $isbn) {
            $favorite = Favorite::query()
                ->where('user_id', $userId)
                ->where('book_ISBN', $isbn)
                ->first();

            if (! $favorite) {
                throw new \Exception('الكتاب غير موجود في المفضلة');
            }

            $favorite->delete();
        });
    }
}
