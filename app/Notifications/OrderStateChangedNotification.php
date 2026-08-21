<?php

namespace App\Notifications;

use App\Models\Order;
use App\Notifications\Concerns\BrandedMail;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderStateChangedNotification extends Notification
{
    use BrandedMail, Queueable;

    public function __construct(public Order $order) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database', 'ably'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return $this->mail()
            ->subject($this->title())
            ->greeting('مرحباً '.$notifiable->fullName())
            ->line($this->body());
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'order_state_changed',
            'order_id' => $this->order->id,
            'state' => $this->order->state?->state,
            'title' => $this->title(),
            'message' => $this->body(),
        ];
    }

    private function title(): string
    {
        return match ($this->order->state?->state) {
            'confirmed' => 'طلبك مؤكد وجاهز للاستلام',
            'delivered' => 'تم تسليم طلبك',
            default => 'تحديث حالة الطلب',
        };
    }

    private function body(): string
    {
        return match ($this->order->state?->state) {
            'confirmed' => 'تم تأكيد طلبك رقم '.$this->order->id.' وخصم النقاط. يرجى الحضور إلى المكتبة لاستلام الكتاب الورقي.',
            'delivered' => 'تم تسليم طلبك رقم '.$this->order->id.' في المكتبة.',
            default => 'تم تحديث حالة طلبك رقم '.$this->order->id.' إلى: '.$this->order->state?->state,
        };
    }
}
