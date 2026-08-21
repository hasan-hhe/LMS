<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LateFineResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type ?: 'late',
            'days_late' => $this->days_late,
            'fine' => $this->fine,
            'fine_points' => $this->fine_points,
            'accrued_until' => $this->accrued_until?->toDateString(),
            'is_paid' => $this->is_paid,
            'paid_at' => $this->paid_at?->toDateTimeString(),
            'paid_via' => $this->paid_via,
            'borrowing' => $this->whenLoaded('borrowing', fn () => [
                'id' => $this->borrowing->id,
                'start_date' => $this->borrowing->start_date?->toDateString(),
                'end_date' => $this->borrowing->end_date?->toDateString(),
                'member' => $this->borrowing->member ? [
                    'id' => $this->borrowing->member->id,
                    'full_name' => $this->borrowing->member->fullName(),
                ] : null,
            ]),
        ];
    }
}
