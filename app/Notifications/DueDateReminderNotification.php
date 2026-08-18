<?php

namespace App\Notifications;

use App\Models\Borrowing;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DueDateReminderNotification extends Notification
{
    use Queueable;

    public function __construct(public Borrowing $borrowing) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('تذكير بموعد إعادة الكتاب')
            ->greeting('مرحباً '.$notifiable->fullName())
            ->line('موعد إعادة كتاب "'.$this->borrowing->bookInstance?->book?->title.'" هو غداً.')
            ->line('يرجى إعادة الكتاب في الموعد لتجنب الغرامة.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'due_date_reminder',
            'borrowing_id' => $this->borrowing->id,
            'book_title' => $this->borrowing->bookInstance?->book?->title,
            'due_date' => $this->borrowing->end_date?->toDateString(),
            'message' => 'موعد إعادة الكتاب غداً',
        ];
    }
}
