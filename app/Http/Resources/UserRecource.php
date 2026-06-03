<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserRecource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'phone_number' => $this->phone,
            'email' => $this->email,
            'adress' => $this->adress,
            'role' => $this->role,
            'state' => $this->state,
            'photo_url' => $this->photo_url,
            'identity_number' => $this->identity_number,
        ];
    }
}
