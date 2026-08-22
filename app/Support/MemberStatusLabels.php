<?php

namespace App\Support;

class MemberStatusLabels
{
    public static function reservation(?string $state): string
    {
        return self::labels()[$state] ?? (self::isArabic((string) $state) ? (string) $state : 'قيد الانتظار');
    }

    public static function order(?string $state): string
    {
        return match ($state) {
            'pending' => 'قيد المراجعة',
            'confirmed' => 'مؤكد',
            'delivered' => 'تم التسليم',
            'cancelled' => 'ملغى',
            'rejected' => 'مرفوض',
            default => self::isArabic((string) $state) ? (string) $state : ($state ?: 'قيد المراجعة'),
        };
    }

    public static function borrowing(bool $returned, bool $overdue): string
    {
        if ($returned) {
            return 'مكتمل';
        }

        return $overdue ? 'متأخر' : 'قيد الاستعارة';
    }

    public static function borrowingStatus(?string $status, bool $returned = false, bool $overdue = false): string
    {
        if ($returned || $status === 'returned') {
            return 'مكتمل';
        }
        if ($overdue || $status === 'overdue') {
            return 'متأخر';
        }

        return 'قيد الاستعارة';
    }

    public static function instance(?string $state): string
    {
        return match ($state) {
            'available' => 'متاح',
            'borrowed' => 'مستعار',
            'reserved' => 'محجوز',
            'damaged' => 'تالف',
            'lost' => 'مفقود',
            default => self::isArabic((string) $state) ? (string) $state : (string) $state,
        };
    }

    public static function any(?string $state): string
    {
        if ($state === null || $state === '') {
            return '';
        }
        if (self::isArabic($state)) {
            return $state;
        }

        return self::labels()[$state]
            ?? self::order($state === 'pending' ? 'pending' : $state);
    }

    public static function notificationState(?string $key, string $domain = 'order'): array
    {
        $label = match ($domain) {
            'reservation' => self::reservation($key),
            'borrowing' => self::borrowingStatus($key),
            'instance' => self::instance($key),
            default => self::order($key),
        };

        return [
            'state_key' => $key,
            'state' => $label,
            'state_label' => $label,
        ];
    }

    public static function localizePayload(array $data): array
    {
        $domain = match ($data['type'] ?? null) {
            'reservation_ready', 'reservation_expired', 'reservation_state_changed' => 'reservation',
            'due_date_reminder', 'borrowing_state_changed', 'fine_charged', 'fine_accumulated', 'damage_fine' => 'borrowing',
            default => 'order',
        };

        $key = $data['state_key'] ?? null;
        $state = $data['state'] ?? null;
        $source = (is_string($key) && $key !== '' && ! self::isArabic($key))
            ? $key
            : ((is_string($state) && $state !== '' && ! self::isArabic($state)) ? $state : null);

        if ($source === null) {
            if (is_string($state) && $state !== '') {
                $data['state_label'] = $data['state_label'] ?? $state;
            }

            return $data;
        }

        $localized = self::notificationState($source, $domain);
        $data['state_key'] = $localized['state_key'];
        $data['state'] = $localized['state'];
        $data['state_label'] = $localized['state_label'];

        return $data;
    }

    private static function labels(): array
    {
        return [
            'pending' => 'قيد الانتظار',
            'confirmed' => 'مؤكد',
            'delivered' => 'تم التسليم',
            'cancelled' => 'ملغى',
            'rejected' => 'مرفوض',
            'ready' => 'جاهز للاستلام',
            'fulfilled' => 'تم الاستلام',
            'available' => 'متاح',
            'borrowed' => 'مستعار',
            'reserved' => 'محجوز',
            'damaged' => 'تالف',
            'lost' => 'مفقود',
            'active' => 'قيد الاستعارة',
            'overdue' => 'متأخر',
            'returned' => 'مكتمل',
        ];
    }

    private static function isArabic(string $value): bool
    {
        return $value !== '' && (bool) preg_match('/\p{Arabic}/u', $value);
    }
}
