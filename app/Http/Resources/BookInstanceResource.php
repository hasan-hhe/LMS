<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookInstanceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'        => $this->id,
            'condition' => $this->condition,
            'state'     => $this->whenLoaded('state', fn() => [
                'id'    => $this->state->id,
                'state' => $this->state->state,
            ]),
            'book'      => $this->whenLoaded('book', fn() => [
                'isbn'  => $this->book->ISBN,
                'title' => $this->book->title,
            ]),
        ];
    }
}
