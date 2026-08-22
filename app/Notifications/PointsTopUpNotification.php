<?php

namespace App\Notifications;

use App\Models\TopUpCode;
use App\Notifications\Concerns\BrandedMail;
use Illuminate\Notifications\Messages\MailMessage;

class PointsTopUpNotification extends QueuedNotification
{
    use BrandedMail;

    public function __construct(public TopUpCode $topUpCode)
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
            ->subject('تم شحن رصيد النقاط')
            ->greeting('مرحباً '.$notifiable->fullName())
            ->line('تمت إضافة '.$this->topUpCode->points_value.' نقطة إلى رصيدك.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'points_top_up',
            'top_up_code_id' => $this->topUpCode->id,
            'points' => $this->topUpCode->points_value,
            'title' => 'تم شحن رصيد النقاط',
            'message' => 'تم شحن رصيد النقاط',
        ];
    }
}
