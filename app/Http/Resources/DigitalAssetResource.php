<?php

namespace App\Http\Resources;

use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DigitalAssetResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = $request->user() ?: $request->user('sanctum');
        $isStaff = $user && in_array($user->role, ['ADMIN', 'LIBRARIAN'], true);
        $hasPurchased = $user && ! $isStaff && OrderItem::query()
            ->where('book_ISBN', $this->book_ISBN)
            ->whereHas('order', fn ($query) => $query
                ->where('user_id', $user->id)
                ->whereHas('state', fn ($state) => $state->where('state', 'confirmed')))
            ->exists();
        $canAccess = $isStaff || $this->is_free || $hasPurchased;

        return [
            'has_digital' => true,
            'locked' => ! $canAccess,
            'is_free' => $this->is_free,
            'pdf_url' => $canAccess ? $this->pdf_url : null,
            'audio_url' => $canAccess ? $this->audio_url : null,
        ];
    }
}
