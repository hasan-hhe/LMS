<?php

namespace App\Notifications;

use App\Models\Reservation;
use App\Notifications\Concerns\BrandedMail;
use App\Support\MemberStatusLabels;
use Illuminate\Notifications\Messages\MailMessage;

class ReservationExpiredNotification extends QueuedNotification
{
    use BrandedMail;

    public function __construct(public Reservation $reservation)
    {
        parent::__construct();
    }

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
            ->line('حالة الحجز: '.MemberStatusLabels::reservation('cancelled').'.')
            ->line('تم إلغاء الحجز وإتاحة النسخة لعضو آخر.');
    }

    public function toArray(object $notifiable): array
    {
        return array_merge(MemberStatusLabels::notificationState('cancelled', 'reservation'), [
            'type' => 'reservation_expired',
            'reservation_id' => $this->reservation->id,
            'book_title' => $this->reservation->bookInstance?->book?->title,
            'title' => 'انتهت مهلة استلام الحجز',
            'message' => 'انتهت مهلة استلام الحجز وتم إلغاؤه. حالة الحجز: '.MemberStatusLabels::reservation('cancelled'),
        ]);
    }
}
