<?php

namespace App\Notifications;

use App\Models\TopUpCode;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PointsTopUpNotification extends Notification
{
    use Queueable;

    public function __construct(public TopUpCode $topUpCode) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
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
            'message' => 'تم شحن رصيد النقاط',
        ];
    }
}
