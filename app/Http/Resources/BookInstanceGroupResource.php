<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookInstanceGroupResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'isbn' => $this->ISBN,
            'title' => $this->title,
            'copies_count' => (int) ($this->instances_count ?? 0),
            'available_count' => (int) ($this->available_count ?? 0),
        ];
    }
}
