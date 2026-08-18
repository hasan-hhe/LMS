<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PointTransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id, 'user_id' => $this->user_id, 'points' => $this->points,
            'type' => $this->type, 'reference_type' => $this->reference_type,
            'reference_id' => $this->reference_id, 'note' => $this->note,
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
