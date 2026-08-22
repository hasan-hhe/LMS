<?php

namespace App\Notifications;

use App\Notifications\Concerns\BrandedMail;
use Illuminate\Notifications\Messages\MailMessage;

class StaffNotification extends QueuedNotification
{
    use BrandedMail;

    public function __construct(
        public string $title,
        public string $body,
        public ?int $senderId = null,
        public bool $sendEmail = false,
    ) {
        parent::__construct();
    }

    public function via(object $notifiable): array
    {
        $channels = ['database', 'ably'];

        if ($this->sendEmail) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        return $this->mail()
            ->subject($this->title)
            ->greeting('مرحباً '.$notifiable->fullName())
            ->line($this->body);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'staff_message',
            'title' => $this->title,
            'message' => $this->body,
            'sender_id' => $this->senderId,
        ];
    }
}
