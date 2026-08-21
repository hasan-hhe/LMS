<?php

namespace App\Console\Commands;

use App\Services\FineService;
use Illuminate\Console\Command;

class AccrueLateFines extends Command
{
    protected $signature = 'fines:accrue-daily';

    protected $description = 'Accrue late fines for overdue borrowings and debit points when possible';

    public function handle(FineService $fineService): int
    {
        $count = $fineService->accrueOverdueFines();
        $this->info("Accrued late fines for {$count} overdue borrowings.");

        return self::SUCCESS;
    }
}
