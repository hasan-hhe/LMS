<?php

namespace App\Console\Commands;

use App\Services\OrderService;
use Illuminate\Console\Command;

class ExpireUnclaimedOrders extends Command
{
    protected $signature = 'orders:expire-unclaimed';

    protected $description = 'Cancel confirmed orders whose pickup window has expired and refund sale stock';

    public function handle(OrderService $orderService): int
    {
        $count = $orderService->expireUnclaimedOrders();
        $this->info("Expired {$count} unclaimed orders.");

        return self::SUCCESS;
    }
}
