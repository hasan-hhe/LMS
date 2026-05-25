<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'isbn'               => $this->ISBN,
            'title'              => $this->title,
            'description'        => $this->discription,
            'price'              => $this->price,
            'amount'             => $this->amount,
            'rate_avg'           => $this->rate_avg,
            'year_of_publishing' => $this->year_of_publishing,
            'number_edition'     => $this->number_edition,
            'cover_url'          => $this->cover_url
                ? asset('storage/' . $this->cover_url)
                : null,
            'author'    => $this->whenLoaded('author', fn() => [
                'id'          => $this->author->id,
                'full_name'   => $this->author->fullName(),
                'nationality' => $this->author->nationality,
            ]),
            'category'  => $this->whenLoaded('category', fn() => [
                'id'    => $this->category->id,
                'title' => $this->category->title,
            ]),
            'publisher' => $this->whenLoaded('publisher', fn() => [
                'id'       => $this->publisher->id,
                'name'     => $this->publisher->name,
                'location' => $this->publisher->location,
            ]),
            'instances_count' => $this->whenLoaded('instances', fn() => $this->instances->count()),
        ];
    }
}
