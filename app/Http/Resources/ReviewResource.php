<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'rate' => $this->rate,
            'comment' => $this->comment,
            'user' => $this->whenLoaded('user', fn () => [
                'id' => $this->user->id,
                'full_name' => $this->user->fullName(),
            ]),
            'book' => $this->whenLoaded('book', fn () => [
                'isbn' => $this->book->ISBN,
                'title' => $this->book->title,
            ]),
        ];
    }
}
