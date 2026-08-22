<?php

namespace App\Http\Resources;

use App\Support\MemberStatusLabels;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReservationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $stateName = $this->state?->state;
        $book = $this->bookInstance?->book;

        return [
            'id'          => $this->id,
            'isbn'        => $book?->ISBN,
            'title'       => $book?->title,
            'author'      => $book?->author?->fullName(),
            'status'      => $stateName,
            'status_label'=> MemberStatusLabels::reservation($stateName),
            'state_name'  => MemberStatusLabels::reservation($stateName),
            'cause'       => $this->cause,
            'notified_at' => $this->notified_at?->toDateTimeString(),
            'reserved_at' => $this->reserved_at?->toDateTimeString(),
            'expires_at'  => $this->expires_at?->toDateTimeString(),
            'state'       => $this->whenLoaded('state', fn() => [
                'id'    => $this->state->id,
                'state' => $this->state->state,
                'key'   => $this->state->state,
                'label' => MemberStatusLabels::reservation($this->state->state),
                'name'  => MemberStatusLabels::reservation($this->state->state),
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
                    'author' => $this->bookInstance->book->author?->fullName(),
                ] : null,
            ]),
            'borrowing' => $this->when(
                $this->relationLoaded('fulfilledBorrowing') && $this->fulfilledBorrowing,
                fn () => new BorrowingResource($this->fulfilledBorrowing)
            ),
        ];
    }
}
