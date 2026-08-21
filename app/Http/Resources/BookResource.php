<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'isbn' => $this->ISBN,
            'title' => $this->title,
            'description' => $this->discription,
            'price' => $this->price,
            'price_points' => $this->price_points,
            'borrow_points' => (int) ($this->borrow_points ?? 0),
            'has_borrow_points' => (int) ($this->borrow_points ?? 0) > 0,
            'amount' => $this->amount,
            'sale_stock' => $this->amount,
            'copies_count' => (int) ($this->instances_count ?? ($this->relationLoaded('instances') ? $this->instances->count() : 0)),
            'instances_count' => (int) ($this->instances_count ?? ($this->relationLoaded('instances') ? $this->instances->count() : 0)),
            'rate_avg' => $this->rate_avg,
            'year_of_publishing' => $this->year_of_publishing,
            'number_edition' => $this->number_edition,
            'cover_url' => $this->cover_url
                ? asset('storage/'.$this->cover_url)
                : null,
            'author' => $this->whenLoaded('author', fn () => [
                'id' => $this->author->id,
                'full_name' => $this->author->fullName(),
                'nationality' => $this->author->nationality,
            ]),
            'category' => $this->whenLoaded('category', fn () => [
                'id' => $this->category->id,
                'title' => $this->category->title,
            ]),
            'publisher' => $this->whenLoaded('publisher', fn () => [
                'id' => $this->publisher->id,
                'name' => $this->publisher->name,
                'location' => $this->publisher->location,
            ]),
            'instances_count' => $this->whenLoaded('instances', fn () => $this->instances->count()),
            'digital' => $this->whenLoaded(
                'digitalAsset',
                fn () => $this->digitalAsset ? new DigitalAssetResource($this->digitalAsset) : null
            ),
        ];
    }
}
