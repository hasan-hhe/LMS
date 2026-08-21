<?php

namespace App\Services;

use App\Models\BookInstance;
use App\Models\InstanceState;
use App\Models\Reservation;
use App\Models\ReservationState;
use App\Notifications\ReservationExpiredNotification;
use App\Notifications\ReservationReadyNotification;
use App\Repositories\Interfaces\ReservationRepositoryInterface;
use Illuminate\Support\Facades\DB;

class ReservationService
{
    public const MAX_ACTIVE_RESERVATIONS = 3;

    public const READY_HOLD_HOURS = 48;

    public function __construct(private ReservationRepositoryInterface $reservationRepository) {}

    public function listReservations(array $filters = []): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        try {
            return $this->reservationRepository->getAllPaginated($filters);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function createReservation(array $data): Reservation
    {
        DB::beginTransaction();
        try {
            $instance = BookInstance::with('state')->find($data['book_instance_id']);
            if (!$instance) {
                throw new \Exception('نسخة الكتاب غير موجودة');
            }

            $this->validateNoExistingReservation($data['user_id'], $instance);
            $this->validateActiveReservationLimit($data['user_id']);

            $pendingState = $this->findOrFailReservationState('pending');

            $reservation = $this->reservationRepository->create([
                'user_id'          => $data['user_id'],
                'book_instance_id' => $data['book_instance_id'],
                'state_id'         => $pendingState->id,
                'cause'            => $data['cause'] ?? '',
                'reserved_at'      => now(),
            ]);

            if ($instance->state?->state === 'available') {
                $this->setInstanceState($instance, 'reserved');
            }

            DB::commit();
            return $reservation->load(['user', 'bookInstance.book', 'state']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function markReady(int $reservationId): Reservation
    {
        DB::beginTransaction();
        try {
            $reservation = $this->reservationRepository->findById($reservationId);
            if (!$reservation) {
                throw new \Exception('الحجز غير موجود');
            }

            $current = $reservation->state?->state;
            if (!in_array($current, ['pending', 'ready'], true)) {
                throw new \Exception('لا يمكن تجهيز هذا الحجز في حالته الحالية');
            }

            $instance = $reservation->bookInstance;
            if (!$instance) {
                throw new \Exception('نسخة الكتاب غير موجودة');
            }

            if (in_array($instance->state?->state, ['borrowed', 'damaged', 'lost'], true)) {
                throw new \Exception('النسخة غير جاهزة للاستلام حالياً');
            }

            $readyState = $this->findOrFailReservationState('ready');
            $updated = $this->reservationRepository->update($reservation, [
                'state_id'    => $readyState->id,
                'notified_at' => now(),
                'expires_at'  => now()->addHours(self::READY_HOLD_HOURS),
            ]);

            $this->setInstanceState($instance, 'reserved');

            DB::commit();
            $updated = $updated->load(['user', 'bookInstance.book', 'state']);
            try {
                $updated->user?->notify(new ReservationReadyNotification($updated));
            } catch (\Throwable $notificationError) {
                report($notificationError);
            }

            return $updated;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function fulfillReservation(int $reservationId): Reservation
    {
        DB::beginTransaction();
        try {
            $reservation = $this->reservationRepository->findById($reservationId);
            if (!$reservation) {
                throw new \Exception('الحجز غير موجود');
            }

            if (!in_array($reservation->state?->state, ['pending', 'ready'], true)) {
                throw new \Exception('لا يمكن تأكيد استلام هذا الحجز');
            }

            $fulfilledState = $this->findOrFailReservationState('fulfilled');
            $updated = $this->reservationRepository->update($reservation, [
                'state_id'    => $fulfilledState->id,
                'notified_at' => $reservation->notified_at ?? now(),
            ]);

            DB::commit();
            return $updated->load(['user', 'bookInstance.book', 'state']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function cancelReservation(int $reservationId): Reservation
    {
        DB::beginTransaction();
        try {
            $reservation = $this->reservationRepository->findById($reservationId);
            if (!$reservation) {
                throw new \Exception('الحجز غير موجود');
            }

            if (in_array($reservation->state?->state, ['cancelled', 'fulfilled'], true)) {
                throw new \Exception('لا يمكن إلغاء هذا الحجز');
            }

            $cancelledState = $this->findOrFailReservationState('cancelled');

            $updated = $this->reservationRepository->update($reservation, [
                'state_id' => $cancelledState->id,
                'expires_at' => null,
            ]);

            $instance = $reservation->bookInstance;
            if ($instance && $instance->state?->state === 'reserved') {
                $stillHeld = Reservation::where('book_instance_id', $instance->id)
                    ->where('id', '!=', $reservation->id)
                    ->whereHas('state', fn ($q) => $q->whereIn('state', ['pending', 'ready']))
                    ->exists();

                if (!$stillHeld) {
                    $this->setInstanceState($instance, 'available');
                }
            }

            DB::commit();
            return $updated->load(['user', 'bookInstance.book', 'state']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function expireUnclaimedReservations(): int
    {
        $expired = Reservation::with(['user', 'bookInstance.book', 'state'])
            ->whereHas('state', fn ($q) => $q->where('state', 'ready'))
            ->where(function ($q) {
                $q->where('expires_at', '<=', now())
                    ->orWhere(function ($inner) {
                        $inner->whereNull('expires_at')
                            ->where('notified_at', '<=', now()->subHours(self::READY_HOLD_HOURS));
                    });
            })
            ->get();

        $count = 0;
        foreach ($expired as $reservation) {
            $instanceId = $reservation->book_instance_id;
            $user = $reservation->user;
            $this->cancelReservation($reservation->id);
            try {
                $user?->notify(new ReservationExpiredNotification($reservation->fresh(['bookInstance.book'])));
            } catch (\Throwable $notificationError) {
                report($notificationError);
            }

            $next = $this->reservationRepository->getNextInQueue($instanceId);
            if ($next) {
                $this->markReady($next->id);
            }
            $count++;
        }

        return $count;
    }

    private function setInstanceState(BookInstance $instance, string $stateName): void
    {
        $state = InstanceState::where('state', $stateName)->first();
        if (!$state) {
            throw new \Exception("حالة النسخة '{$stateName}' غير موجودة في قاعدة البيانات");
        }
        $instance->update(['state_id' => $state->id]);
    }

    private function validateNoExistingReservation(int $userId, BookInstance $instance): void
    {
        $sameCopy = Reservation::where('user_id', $userId)
            ->where('book_instance_id', $instance->id)
            ->whereHas('state', fn ($q) => $q->whereIn('state', ['pending', 'ready']))
            ->exists();

        if ($sameCopy) {
            throw new \Exception('لديك حجز نشط مسبق لهذه النسخة');
        }

        $sameTitle = Reservation::where('user_id', $userId)
            ->whereHas('state', fn ($q) => $q->whereIn('state', ['pending', 'ready']))
            ->whereHas('bookInstance', fn ($q) => $q->where('book_ISBN', $instance->book_ISBN))
            ->exists();

        if ($sameTitle) {
            throw new \Exception('لديك حجز نشط مسبق لنفس الكتاب');
        }
    }

    private function validateActiveReservationLimit(int $userId): void
    {
        $activeCount = Reservation::where('user_id', $userId)
            ->whereHas('state', fn ($q) => $q->whereIn('state', ['pending', 'ready']))
            ->count();

        if ($activeCount >= self::MAX_ACTIVE_RESERVATIONS) {
            throw new \Exception('وصل العضو للحد الأقصى للحجوزات النشطة ('.self::MAX_ACTIVE_RESERVATIONS.')');
        }
    }

    private function findOrFailReservationState(string $stateName): ReservationState
    {
        $state = ReservationState::where('state', $stateName)->first();
        if (!$state) {
            throw new \Exception("حالة الحجز '{$stateName}' غير موجودة في قاعدة البيانات");
        }
        return $state;
    }
}
