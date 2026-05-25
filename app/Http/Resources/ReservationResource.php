<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReservationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'cause'       => $this->cause,
            'notified_at' => $this->notified_at?->toDateTimeString(),
            'reserved_at' => $this->reserved_at?->toDateTimeString(),
            'state'       => $this->whenLoaded('state', fn() => [
                'id'    => $this->state->id,
                'state' => $this->state->state,
            ]),
            'user'        => $this->whenLoaded('user', fn() => [
                'id'        => $this->user->id,
                'full_name' => $this->user->fullName(),
                'email'     => $this->user->email,
            ]),
            'book_instance' => $this->whenLoaded('bookInstance', fn() => [
                'id'        => $this->bookInstance->id,
                'condition' => $this->bookInstance->condition,
                'book'      => $this->bookInstance->book ? [
                    'isbn'  => $this->bookInstance->book->ISBN,
                    'title' => $this->bookInstance->book->title,
                ] : null,
            ]),
        ];
    }
}
