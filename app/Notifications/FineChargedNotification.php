<?php

namespace App\Notifications;

use App\Models\LateFine;
use App\Notifications\Concerns\BrandedMail;
use Illuminate\Notifications\Messages\MailMessage;

class FineChargedNotification extends QueuedNotification
{
    use BrandedMail;

    public function __construct(
        public LateFine $fine,
        public int $pointsDebited,
        public float $sypDebited,
    ) {
        parent::__construct();
    }

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
