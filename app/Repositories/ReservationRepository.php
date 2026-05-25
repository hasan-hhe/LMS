<?php

namespace App\Repositories;

use App\Models\Reservation;
use App\Repositories\Interfaces\ReservationRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ReservationRepository implements ReservationRepositoryInterface
{
    public function getAllPaginated(int $perPage = 15): LengthAwarePaginator
    {
        return Reservation::with(['user', 'bookInstance.book', 'state'])
            ->orderByDesc('reserved_at')
            ->paginate($perPage);
    }

    public function findById(int $id): ?Reservation
    {
        return Reservation::with(['user', 'bookInstance.book', 'state'])->find($id);
    }

    public function create(array $data): Reservation
    {
        return Reservation::create($data);
    }

    public function update(Reservation $reservation, array $data): Reservation
    {
        $reservation->update($data);
        return $reservation->fresh(['user', 'bookInstance.book', 'state']);
    }

    public function getNextInQueue(int $bookInstanceId): ?Reservation
    {
        return Reservation::with('user')
            ->where('book_instance_id', $bookInstanceId)
            ->whereHas('state', fn($q) => $q->where('state', 'pending'))
            ->orderBy('reserved_at')
            ->first();
    }
}
