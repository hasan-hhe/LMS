<?php

namespace App\Listeners;

use Illuminate\Mail\Events\MessageSending;
use Symfony\Component\Mime\Email;

class EmbedSystemLogoInMail
{
    public function handle(MessageSending $event): void
    {
        $message = $event->message;

        if (! $message instanceof Email) {
            return;
        }

        $path = public_path('assets/img/libralms-mark.png');

        if (! is_file($path)) {
            return;
        }

        $message->embedFromPath($path, 'libralms-logo', 'image/png');
    }
}
