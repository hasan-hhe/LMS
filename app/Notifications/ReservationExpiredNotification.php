<?php

namespace App\Notifications;

use App\Models\Reservation;
use App\Notifications\Concerns\BrandedMail;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReservationExpiredNotification extends Notification
{
    use BrandedMail, Queueable;

    public function __construct(public Reservation $reservation) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database', 'ably'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return $this->mail()
            ->subject('انتهت مهلة استلام الحجز')
            ->greeting('مرحباً '.$notifiable->fullName())
            ->line('انتهت مهلة استلام كتاب "'.$this->reservation->bookInstance?->book?->title.'" ولم يتم استلامه.')
            ->line('تم إلغاء الحجز وإتاحة النسخة لعضو آخر.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'reservation_expired',
            'reservation_id' => $this->reservation->id,
            'book_title' => $this->reservation->bookInstance?->book?->title,
            'title' => 'انتهت مهلة استلام الحجز',
            'message' => 'انتهت مهلة استلام الحجز وتم إلغاؤه',
        ];
    }
}
