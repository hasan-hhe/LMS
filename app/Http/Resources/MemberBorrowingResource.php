<?php

namespace App\Http\Resources;

use App\Support\MemberStatusLabels;
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
        $extension = $this->extensionQuote();

        return [
            'id'             => $this->id,
            'title'          => $book?->title,
            'author'         => $author ? $author->fullName() : null,
            'isbn'           => $book?->ISBN,
            'status'         => $isOverdue ? 'overdue' : 'active',
            'status_label'   => MemberStatusLabels::borrowing(false, $isOverdue),
            'state_name'     => MemberStatusLabels::borrowing(false, $isOverdue),
            'end_date'       => $this->end_date?->toDateString(),
            'is_overdue'     => $isOverdue,
            'days_overdue'   => $daysOverdue,
            'days_until_due' => max(0, $daysUntilDue),
            'borrow_points'  => (int) ($book?->borrow_points ?? 0),
            'has_borrow_points' => (int) ($book?->borrow_points ?? 0) > 0,
            'borrow_days'    => $book?->loanPeriodDays() ?? 14,
            'extension'      => $extension,
            'extension_points' => $extension['points'] ?? 0,
        ];
    }

    private function extensionQuote(): array
    {
        try {
            return app(\App\Services\BorrowingService::class)->quoteExtension((int) $this->id);
        } catch (\Throwable) {
            return [
                'can_extend' => false,
                'points' => 0,
            ];
        }
    }
}
