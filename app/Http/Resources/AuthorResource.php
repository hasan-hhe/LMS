<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuthorResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'firstname'   => $this->firstname,
            'lastname'    => $this->lastname,
            'full_name'   => $this->fullName(),
            'nationality' => $this->nationality,
            'books_count' => $this->whenLoaded('books', fn() => $this->books->count()),
        ];
    }
}
