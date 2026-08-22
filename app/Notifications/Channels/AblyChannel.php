<?php

namespace App\Notifications\Channels;

use App\Services\AblyService;
use App\Support\MemberStatusLabels;
use Illuminate\Notifications\Notification;

class AblyChannel
{
    public function __construct(private AblyService $ably) {}

    public function send(object $notifiable, Notification $notification): void
    {
        if (! $this->ably->isEnabled() || ! method_exists($notifiable, 'getKey')) {
            return;
        }

        try {
            $data = method_exists($notification, 'toAbly')
                ? $notification->toAbly($notifiable)
                : $notification->toArray($notifiable);

            if (is_array($data)) {
                $data = MemberStatusLabels::localizePayload($data);
            }

            $this->ably->publishUserNotification((int) $notifiable->getKey(), [
                'id' => $notification->id,
                'type' => $notification::class,
                'data' => $data,
                'read_at' => null,
                'created_at' => now()->toIso8601String(),
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
