<?php

namespace App\Http\Resources;

use App\Support\MemberStatusLabels;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DashboardNotificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $data = MemberStatusLabels::localizePayload($this->data ?? []);

        return [
            'id' => $this->id,
            'title' => $data['title'] ?? '',
            'message' => $data['message'] ?? $data['body'] ?? '',
            'state' => $data['state'] ?? null,
            'state_label' => $data['state_label'] ?? null,
            'user' => $this->whenLoaded('notifiable', fn () => $this->notifiable ? [
                'id' => $this->notifiable->id,
                'full_name' => method_exists($this->notifiable, 'fullName')
                    ? $this->notifiable->fullName()
                    : ($this->notifiable->email ?? null),
                'email' => $this->notifiable->email ?? null,
            ] : null),
            'read_at' => $this->read_at,
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
