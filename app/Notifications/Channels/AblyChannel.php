<?php

namespace App\Notifications\Channels;

use App\Services\AblyService;
use Illuminate\Notifications\Notification;

class AblyChannel
{
    public function __construct(private AblyService $ably) {}

    public function send(object $notifiable, Notification $notification): void
    {
        if (! $this->ably->isEnabled() || ! method_exists($notifiable, 'getKey')) {
            return;
        }

        $data = method_exists($notification, 'toAbly')
            ? $notification->toAbly($notifiable)
            : $notification->toArray($notifiable);

        $this->ably->publishUserNotification((int) $notifiable->getKey(), [
            'id' => $notification->id,
            'type' => $notification::class,
            'data' => $data,
            'read_at' => null,
            'created_at' => now()->toIso8601String(),
        ]);
    }
}
