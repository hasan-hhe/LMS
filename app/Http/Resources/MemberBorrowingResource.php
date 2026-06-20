<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MemberBorrowingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $book = $this->bookInstance?->book;
        $author = $book?->author;
        $isOverdue = $this->isOverdue();
        $daysOverdue = $isOverdue
            ? (int) $this->end_date->startOfDay()->diffInDays(now()->startOfDay())
            : 0;
        $daysUntilDue = !$isOverdue
            ? (int) now()->startOfDay()->diffInDays($this->end_date->startOfDay(), false)
            : 0;

        return [
            'id'             => $this->id,
            'title'          => $book?->title,
            'author'         => $author ? $author->fullName() : null,
            'isbn'           => $book?->ISBN,
            'end_date'       => $this->end_date?->toDateString(),
            'is_overdue'     => $isOverdue,
            'days_overdue'   => $daysOverdue,
            'days_until_due' => max(0, $daysUntilDue),
        ];
    }
}
