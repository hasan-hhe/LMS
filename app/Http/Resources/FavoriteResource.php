<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FavoriteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $book = $this->book;
        $available = (int) ($book?->available_count ?? 0);

        return [
            'id' => $this->id,
            'isbn' => $this->book_ISBN ?? $book?->ISBN,
            'title' => $book?->title,
            'author' => $book?->author?->fullName(),
            'available_copies' => $available,
            'available_count' => $available,
            'copies_count' => (int) ($book?->instances_count ?? 0),
            'sale_stock' => (int) ($book?->amount ?? 0),
            'rating' => $book?->rate_avg,
            'rate_avg' => $book?->rate_avg,
            'price_syp' => $book?->price,
            'price_points' => $book?->price_points,
            'borrow_points' => (int) ($book?->borrow_points ?? 0),
            'borrow_days' => $book?->loanPeriodDays() ?? 14,
            'cover_url' => $book?->cover_url ? asset('storage/'.$book->cover_url) : null,
            'digital' => $book?->digitalAsset
                ? new DigitalAssetResource($book->digitalAsset)
                : null,
            'book' => $book ? new BookResource($book) : null,
            'created_at' => $this->created_at,
        ];
    }
}
