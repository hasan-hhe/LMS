<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'first_name'      => $this->first_name,
            'last_name'       => $this->last_name,
            'full_name'       => $this->fullName(),
            'email'           => $this->email,
            'phone'           => $this->phone,
            'identity_number' => $this->identity_number,
            'adress'          => $this->adress,
            'role'            => $this->role,
            'photo_url'       => $this->photo_url
                ? asset('storage/' . $this->photo_url)
                : null,
            'participe_end_date' => $this->participe_end_date?->toDateString(),
            'created_at'      => $this->created_at?->toDateTimeString(),
        ];
    }
}
