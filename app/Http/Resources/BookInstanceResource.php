<?php

namespace App\Http\Resources;

use App\Support\MemberStatusLabels;
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
                'key'   => $this->state->state,
                'label' => MemberStatusLabels::instance($this->state->state),
                'name'  => MemberStatusLabels::instance($this->state->state),
            ]),
            'book'      => $this->whenLoaded('book', fn() => [
                'isbn'  => $this->book->ISBN,
                'title' => $this->book->title,
                'borrow_points' => (int) ($this->book->borrow_points ?? 0),
                'borrow_days' => $this->book->loanPeriodDays(),
            ]),
        ];
    }
}
