<?php

namespace App\Notifications\Channels;

use App\Jobs\SendQueuedMailNotification;
use Illuminate\Notifications\Notification;

class QueuedMailChannel
{
    public function send(object $notifiable, Notification $notification): void
    {
        $job = new SendQueuedMailNotification($notifiable, $notification);

        if ($this->shouldSendInline()) {
            dispatch_sync($job);

            return;
        }

        dispatch($job)->onConnection($this->asyncConnection());
    }

    private function shouldSendInline(): bool
    {
        return app()->runningUnitTests() || $this->runningQueueWorker();
    }

    private function runningQueueWorker(): bool
    {
        foreach ($_SERVER['argv'] ?? [] as $arg) {
            if (in_array($arg, ['queue:work', 'queue:listen', 'horizon', 'horizon:work'], true)) {
                return true;
            }
        }

        return false;
    }

    private function asyncConnection(): string
    {
        $default = (string) config('queue.default', 'database');

        return in_array($default, ['sync', 'deferred', 'background'], true)
            ? 'database'
            : $default;
    }
}
