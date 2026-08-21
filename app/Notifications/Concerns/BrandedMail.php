<?php

namespace App\Notifications\Concerns;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\HtmlString;

trait BrandedMail
{
    protected function mail(): MailMessage
    {
        $name = config('app.name', 'LibraLMS');

        return (new MailMessage)
            ->from(config('mail.from.address'), $name)
            ->salutation(new HtmlString('مع التحية،<br>'.e($name)));
    }
}
