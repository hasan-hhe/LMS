<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderStateChangedNotification extends Notification
{
    use Queueable;

    public function __construct(public Order $order) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('تحديث حالة الطلب')
            ->greeting('مرحباً '.$notifiable->fullName())
            ->line('تم تحديث حالة طلبك رقم '.$this->order->id.' إلى: '.$this->order->state?->state);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'order_state_changed',
            'order_id' => $this->order->id,
            'state' => $this->order->state?->state,
            'message' => 'تم تحديث حالة طلبك',
        ];
    }
}
