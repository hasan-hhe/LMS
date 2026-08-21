<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\StaffNotification;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;

class NotificationService
{
    public function list(array $filters = []): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = DatabaseNotification::query()
            ->with('notifiable')
            ->latest();

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where('data', 'like', "%{$search}%");
        }

        $perPage = min(100, max(1, (int) ($filters['per_page'] ?? 15)));

        return $query->paginate($perPage);
    }

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
