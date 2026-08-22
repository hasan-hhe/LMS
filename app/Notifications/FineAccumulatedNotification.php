<?php

namespace App\Notifications;

use App\Models\LateFine;
use App\Notifications\Concerns\BrandedMail;
use Illuminate\Notifications\Messages\MailMessage;

class FineAccumulatedNotification extends QueuedNotification
{
    use BrandedMail;

    public function __construct(public LateFine $fine, public int $addedPoints, public float $addedSyp)
    {
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
            ->subject('تراكمت غرامة تأخير على حسابك')
            ->greeting('مرحباً '.$notifiable->fullName())
            ->line('رصيدك لا يكفي لخصم غرامة يوم تأخير على كتاب "'.$title.'".')
            ->line('أُضيف '.$this->addedPoints.' نقطة / '.$this->addedSyp.' ل.س إلى الغرامة المستحقة.')
            ->line('المستحق الحالي: '.$this->fine->fine_points.' نقطة أو '.$this->fine->fine.' ل.س.')
            ->line('ستتوقف الاستعارة والحجز والشراء حتى تسوية الغرامة.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'fine_accumulated',
            'fine_id' => $this->fine->id,
            'borrowing_id' => $this->fine->borrowing_id,
            'fine_points' => $this->fine->fine_points,
            'fine_syp' => $this->fine->fine,
            'title' => 'غرامة تأخير متراكمة',
            'message' => 'رصيدك لا يكفي، تراكمت غرامة '.$this->fine->fine_points.' نقطة أو '.$this->fine->fine.' ل.س',
        ];
    }
}
