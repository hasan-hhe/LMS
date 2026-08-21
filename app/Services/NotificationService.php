<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\StaffNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;

class NotificationService
{
    public function send(array $data, int $senderId): array
    {
        $recipients = $this->recipients($data);

        if ($recipients->isEmpty()) {
            throw new \Exception('لا يوجد مستلمون للإشعار');
        }

        Notification::send($recipients, new StaffNotification(
            $data['title'],
            $data['body'],
            $senderId,
            (bool) ($data['send_email'] ?? false),
        ));

        return [
            'sent_count' => $recipients->count(),
            'audience' => $data['audience'],
        ];
    }

    private function recipients(array $data): Collection
    {
        if (($data['audience'] ?? '') === 'selected') {
            return User::query()
                ->whereIn('id', $data['user_ids'] ?? [])
                ->get();
        }

        return User::query()
            ->where('role', 'MEMBER')
            ->get();
    }
}
