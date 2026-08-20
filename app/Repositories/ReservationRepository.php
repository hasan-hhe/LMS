<?php

namespace App\Repositories;

use App\Models\Reservation;
use App\Repositories\Interfaces\ReservationRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ReservationRepository implements ReservationRepositoryInterface
{
    public function getAllPaginated(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Reservation::with(['user', 'bookInstance.book', 'state']);

        if (! empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (! empty($filters['book_instance_id'])) {
            $query->where('book_instance_id', $filters['book_instance_id']);
        }

        if (! empty($filters['state'])) {
            $query->whereHas('state', fn ($q) => $q->where('state', $filters['state']));
        }

        return $query->orderByDesc('reserved_at')->paginate($perPage);
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
