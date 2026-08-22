<?php

namespace App\Jobs;

use Illuminate\Contracts\Mail\Factory as MailFactory;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Mail\Markdown;
use Illuminate\Notifications\Channels\MailChannel;
use Illuminate\Notifications\Notification;

class SendQueuedMailNotification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(
        public object $notifiable,
        public Notification $notification,
    ) {
        if (app()->runningUnitTests()) {
            return;
        }

        $this->afterCommit();

        $default = (string) config('queue.default', 'database');
        if (in_array($default, ['sync', 'deferred', 'background'], true)) {
            $this->onConnection('database');
        }
    }

    public function handle(MailFactory $mailer, Markdown $markdown): void
    {
        (new MailChannel($mailer, $markdown))->send($this->notifiable, $this->notification);
    }
}
