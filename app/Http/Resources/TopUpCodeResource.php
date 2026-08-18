<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TopUpCodeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id, 'code' => $this->code, 'points_value' => $this->points_value,
            'user_id' => $this->user_id, 'is_used' => $this->is_used,
            'expires_at' => $this->expires_at?->toDateTimeString(),
            'used_at' => $this->used_at?->toDateTimeString(),
            'used_by' => $this->used_by, 'created_by' => $this->created_by,
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
