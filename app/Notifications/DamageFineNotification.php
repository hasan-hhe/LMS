<?php

namespace App\Notifications;

use App\Models\LateFine;
use Illuminate\Notifications\Messages\MailMessage;

class DamageFineNotification extends QueuedNotification
{
    public function __construct(public LateFine $fine)
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
        $kind = $this->fine->type === 'lost' ? 'فقدان' : 'إتلاف';

        return (new MailMessage)
            ->subject('غرامة '.$kind.' كتاب')
            ->greeting('مرحباً '.$notifiable->fullName())
            ->line('تم تسجيل غرامة '.$kind.' على كتاب "'.$title.'".')
            ->line('المستحق: '.$this->fine->fine_points.' نقطة أو '.$this->fine->fine.' ل.س.')
            ->line('ستتوقف الاستعارة والحجز والشراء حتى تسوية الغرامة.');
    }

    public function toArray(object $notifiable): array
    {
        $kind = $this->fine->type === 'lost' ? 'فقدان' : 'إتلاف';

        return [
            'type' => 'damage_fine',
            'fine_id' => $this->fine->id,
            'borrowing_id' => $this->fine->borrowing_id,
            'fine_type' => $this->fine->type,
            'fine_points' => $this->fine->fine_points,
            'fine_syp' => $this->fine->fine,
            'title' => 'غرامة '.$kind.' كتاب',
            'message' => 'تم تسجيل غرامة '.$kind.' بقيمة '.$this->fine->fine_points.' نقطة أو '.$this->fine->fine.' ل.س',
        ];
    }
}
