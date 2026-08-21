<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'title'       => $this->title,
            'description' => $this->discription,
            'books_count' => (int) ($this->books_count ?? ($this->relationLoaded('books') ? $this->books->count() : 0)),
        ];
    }
}
