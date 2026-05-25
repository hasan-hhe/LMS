<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BorrowingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'start_date'     => $this->start_date?->toDateString(),
            'end_date'       => $this->end_date?->toDateString(),
            'due_date'       => $this->due_date?->toDateString(),
            'returned_at'    => $this->returned_at?->toDateTimeString(),
            'is_returned'    => $this->isReturned(),
            'is_overdue'     => $this->isOverdue(),
            'borrowing_cost' => $this->borrowing_cast,
            'is_paid'        => $this->is_paid,
            'paid_at'        => $this->paid_at?->toDateTimeString(),
            'member'         => $this->whenLoaded('member', fn() => [
                'id'        => $this->member->id,
                'full_name' => $this->member->fullName(),
                'email'     => $this->member->email,
            ]),
            'librarian'      => $this->whenLoaded('librarian', fn() => [
                'id'        => $this->librarian->id,
                'full_name' => $this->librarian->fullName(),
            ]),
            'book_instance'  => $this->whenLoaded('bookInstance', fn() => [
                'id'        => $this->bookInstance->id,
                'condition' => $this->bookInstance->condition,
                'book'      => $this->bookInstance->book ? [
                    'isbn'  => $this->bookInstance->book->ISBN,
                    'title' => $this->bookInstance->book->title,
                ] : null,
            ]),
            'late_fine'      => $this->whenLoaded('lateFine', fn() => $this->lateFine ? [
                'days_late' => $this->lateFine->days_late,
                'fine'      => $this->lateFine->fine,
                'is_paid'   => $this->lateFine->is_paid,
            ] : null),
        ];
    }
}
