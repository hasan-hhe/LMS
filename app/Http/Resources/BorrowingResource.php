<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BorrowingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $extension = $this->extensionQuote();

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
            'extension'      => $extension,
            'extension_points' => $extension['points'] ?? 0,
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
                    'borrow_points' => (int) ($this->bookInstance->book->borrow_points ?? 0),
                    'has_borrow_points' => (int) ($this->bookInstance->book->borrow_points ?? 0) > 0,
                ] : null,
            ]),
            'late_fine'      => $this->whenLoaded('lateFine', fn() => $this->lateFine ? [
                'id'          => $this->lateFine->id,
                'type'        => $this->lateFine->type ?: 'late',
                'days_late'   => $this->lateFine->days_late,
                'fine'        => $this->lateFine->fine,
                'fine_points' => $this->lateFine->fine_points,
                'is_paid'     => $this->lateFine->is_paid,
            ] : null),
            'damage_fine'    => $this->whenLoaded('damageFine', fn() => $this->damageFine ? [
                'id'          => $this->damageFine->id,
                'type'        => $this->damageFine->type,
                'fine'        => $this->damageFine->fine,
                'fine_points' => $this->damageFine->fine_points,
                'is_paid'     => $this->damageFine->is_paid,
            ] : null),
        ];
    }

    private function extensionQuote(): array
    {
        try {
            return app(\App\Services\BorrowingService::class)->quoteExtension((int) $this->id);
        } catch (\Throwable) {
            return [
                'can_extend' => false,
                'already_extended' => false,
                'points' => 0,
                'days' => 0,
                'new_end_date' => null,
                'reason' => null,
            ];
        }
    }
}
