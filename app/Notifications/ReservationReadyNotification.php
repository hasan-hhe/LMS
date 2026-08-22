<?php

namespace App\Notifications;

use App\Models\Reservation;
use App\Notifications\Concerns\BrandedMail;
use App\Support\MemberStatusLabels;
use Illuminate\Notifications\Messages\MailMessage;

class ReservationReadyNotification extends QueuedNotification
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
            ->subject('حجزك جاهز للاستلام')
            ->greeting('مرحباً '.$notifiable->fullName())
            ->line('أصبح كتاب "'.$this->reservation->bookInstance?->book?->title.'" جاهزاً للاستلام.')
            ->line('حالة الحجز: '.MemberStatusLabels::reservation('ready').'.')
            ->line('يرجى استلامه خلال 48 ساعة وإلا سيُلغى الحجز تلقائياً.');
    }

    public function toArray(object $notifiable): array
    {
        return array_merge(MemberStatusLabels::notificationState('ready', 'reservation'), [
            'type' => 'reservation_ready',
            'reservation_id' => $this->reservation->id,
            'book_title' => $this->reservation->bookInstance?->book?->title,
            'title' => 'حجزك جاهز للاستلام',
            'message' => 'حجزك جاهز للاستلام. حالة الحجز: '.MemberStatusLabels::reservation('ready'),
        ]);
    }
}
