<?php

namespace App\Console\Commands;

use App\Services\ReservationService;
use Illuminate\Console\Command;

class ExpireUnclaimedReservations extends Command
{
    protected $signature = 'reservations:expire-unclaimed';

    protected $description = 'Cancel ready reservations whose pickup window has expired';

    public function handle(ReservationService $reservationService): int
    {
        $count = $reservationService->expireUnclaimedReservations();
        $this->info("Expired {$count} unclaimed reservations.");

        return self::SUCCESS;
    }
}
