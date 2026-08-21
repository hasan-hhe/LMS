<?php

namespace App\Notifications;

use App\Models\LateFine;
use App\Notifications\Concerns\BrandedMail;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class FineChargedNotification extends Notification
{
    use BrandedMail, Queueable;

    public function __construct(
        public LateFine $fine,
        public int $pointsDebited,
        public float $sypDebited,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database', 'ably'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $title = $this->fine->borrowing?->bookInstance?->book?->title ?? 'كتاب';

        return $this->mail()
            ->subject('تم خصم غرامة تأخير من رصيدك')
            ->greeting('مرحباً '.$notifiable->fullName())
            ->line('تم خصم '.$this->pointsDebited.' نقطة مقابل يوم تأخير على كتاب "'.$title.'".')
            ->line('المقابل المرجعي: '.$this->sypDebited.' ل.س.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'fine_charged',
            'fine_id' => $this->fine->id,
            'borrowing_id' => $this->fine->borrowing_id,
            'points' => $this->pointsDebited,
            'syp' => $this->sypDebited,
            'title' => 'خصم غرامة تأخير',
            'message' => 'تم خصم '.$this->pointsDebited.' نقطة غرامة تأخير من رصيدك',
        ];
    }
}
