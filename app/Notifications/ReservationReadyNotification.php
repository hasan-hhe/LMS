<?php

namespace App\Notifications;

use App\Models\Reservation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReservationReadyNotification extends Notification
{
    use Queueable;

    public function __construct(public Reservation $reservation) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database', 'ably'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('حجزك جاهز للاستلام')
            ->greeting('مرحباً '.$notifiable->fullName())
            ->line('أصبح كتاب "'.$this->reservation->bookInstance?->book?->title.'" جاهزاً للاستلام.')
            ->line('يرجى استلامه خلال 48 ساعة وإلا سيُلغى الحجز تلقائياً.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'reservation_ready',
            'reservation_id' => $this->reservation->id,
            'book_title' => $this->reservation->bookInstance?->book?->title,
            'title' => 'حجزك جاهز للاستلام',
            'message' => 'حجزك جاهز للاستلام',
        ];
    }
}
