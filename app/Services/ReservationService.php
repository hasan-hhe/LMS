<?php

namespace App\Services;

use App\Models\Reservation;
use App\Models\ReservationState;
use App\Repositories\Interfaces\ReservationRepositoryInterface;
use Illuminate\Support\Facades\DB;

class ReservationService
{
    public function __construct(private ReservationRepositoryInterface $reservationRepository) {}

    public function listReservations(): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        try {
            return $this->reservationRepository->getAllPaginated();
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function createReservation(array $data): Reservation
    {
        DB::beginTransaction();
        try {
            $this->validateNoExistingReservation($data['user_id'], $data['book_instance_id']);

            $pendingState = $this->findOrFailReservationState('pending');

            $reservation = $this->reservationRepository->create([
                'user_id'          => $data['user_id'],
                'book_instance_id' => $data['book_instance_id'],
                'state_id'         => $pendingState->id,
                'cause'            => $data['cause'] ?? null,
                'reserved_at'      => now(),
            ]);

            DB::commit();
            return $reservation->load(['user', 'bookInstance.book', 'state']);
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

            $cancelledState = $this->findOrFailReservationState('cancelled');

            $updated = $this->reservationRepository->update($reservation, [
                'state_id' => $cancelledState->id,
            ]);

            DB::commit();
            return $updated;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    private function validateNoExistingReservation(int $userId, int $bookInstanceId): void
    {
        $exists = Reservation::where('user_id', $userId)
            ->where('book_instance_id', $bookInstanceId)
            ->whereHas('state', fn($q) => $q->where('state', 'pending'))
            ->exists();

        if ($exists) {
            throw new \Exception('لديك حجز نشط مسبق لهذه النسخة');
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
