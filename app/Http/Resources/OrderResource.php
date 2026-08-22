<?php

namespace App\Http\Resources;

use App\Support\MemberStatusLabels;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $stateName = $this->state?->state;

        return [
            'id' => $this->id,
            'total_prices' => $this->total_prices,
            'total_points' => $this->total_points,
            'total_amount' => $this->total_amount,
            'pickup_expires_at' => $this->pickup_expires_at?->toDateTimeString(),
            'status' => $stateName,
            'status_label' => MemberStatusLabels::order($stateName),
            'state_name' => MemberStatusLabels::order($stateName),
            'reason' => $this->state_reason,
            'state_reason' => $this->state_reason,
            'state' => $this->whenLoaded('state', fn () => [
                'id' => $this->state->id,
                'state' => $this->state->state,
                'key' => $this->state->state,
                'label' => MemberStatusLabels::order($this->state->state),
                'name' => MemberStatusLabels::order($this->state->state),
            ]),
            'user' => $this->whenLoaded('user', fn () => [
                'id' => $this->user->id,
                'full_name' => $this->user->fullName(),
            ]),
            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn ($item) => [
                'id' => $item->id,
                'price_once' => $item->price_once,
                'count' => $item->count,
                'format' => $item->format ?: 'paper',
                'total' => $item->totalPrice(),
                'book' => $item->book ? [
                    'isbn' => $item->book->ISBN,
                    'title' => $item->book->title,
                ] : null,
            ])),
        ];
    }
}
