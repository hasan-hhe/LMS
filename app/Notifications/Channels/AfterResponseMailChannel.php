<?php

namespace App\Notifications\Channels;

use Illuminate\Contracts\Mail\Factory as MailFactory;
use Illuminate\Mail\Markdown;
use Illuminate\Notifications\Channels\MailChannel;
use Illuminate\Notifications\Notification;
use Throwable;

class AfterResponseMailChannel
{
    public function __construct(
        private MailFactory $mailer,
        private Markdown $markdown,
    ) {}

    public function send(object $notifiable, Notification $notification): void
    {
        $mailer = $this->mailer;
        $markdown = $this->markdown;

        $deliver = function () use ($notifiable, $notification, $mailer, $markdown): void {
            (new MailChannel($mailer, $markdown))->send($notifiable, $notification);
        };

        if (app()->runningUnitTests()) {
            $deliver();

            return;
        }

        dispatch(function () use ($deliver): void {
            try {
                $deliver();
            } catch (Throwable $e) {
                report($e);
            }
        })->afterResponse();
    }
}
