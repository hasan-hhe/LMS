<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'total_prices' => $this->total_prices,
            'total_amount' => $this->total_amount,
            'state'        => $this->whenLoaded('state', fn() => [
                'id'    => $this->state->id,
                'state' => $this->state->state,
            ]),
            'user'         => $this->whenLoaded('user', fn() => [
                'id'        => $this->user->id,
                'full_name' => $this->user->fullName(),
            ]),
            'items'        => $this->whenLoaded('items', fn() => $this->items->map(fn($item) => [
                'id'         => $item->id,
                'price_once' => $item->price_once,
                'count'      => $item->count,
                'total'      => $item->totalPrice(),
                'book'       => $item->book ? [
                    'isbn'  => $item->book->ISBN,
                    'title' => $item->book->title,
                ] : null,
            ])),
        ];
    }
}
