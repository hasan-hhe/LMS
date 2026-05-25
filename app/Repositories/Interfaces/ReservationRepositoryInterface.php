<?php

namespace App\Repositories\Interfaces;

use App\Models\Reservation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ReservationRepositoryInterface
{
    public function getAllPaginated(int $perPage = 15): LengthAwarePaginator;
    public function findById(int $id): ?Reservation;
    public function create(array $data): Reservation;
    public function update(Reservation $reservation, array $data): Reservation;
    public function getNextInQueue(int $bookInstanceId): ?Reservation;
}
