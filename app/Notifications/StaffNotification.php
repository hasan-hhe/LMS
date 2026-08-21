<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class StaffNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $title,
        public string $body,
        public ?int $senderId = null,
        public bool $sendEmail = false,
    ) {}

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
        return (new MailMessage)
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
