<?php

namespace App\Console\Commands;

use App\Models\Borrowing;
use App\Notifications\DueDateReminderNotification;
use Illuminate\Console\Command;

class SendDueReminders extends Command
{
    protected $signature = 'borrowings:send-due-reminders';

    protected $description = 'Send reminders for borrowings due tomorrow';

    public function handle(): int
    {
        $count = 0;
        Borrowing::with(['member', 'bookInstance.book'])
            ->whereNull('returned_at')
            ->whereDate('end_date', now()->addDay()->toDateString())
            ->chunkById(100, function ($borrowings) use (&$count): void {
                foreach ($borrowings as $borrowing) {
                    $borrowing->member?->notify(new DueDateReminderNotification($borrowing));
                    $count++;
                }
            });

        $this->info("Sent {$count} due-date reminders.");

        return self::SUCCESS;
    }
}
