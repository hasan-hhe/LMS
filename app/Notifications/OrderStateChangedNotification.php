<?php

namespace App\Notifications;

use App\Models\Order;
use App\Notifications\Concerns\BrandedMail;
use App\Support\MemberStatusLabels;
use Illuminate\Notifications\Messages\MailMessage;

class OrderStateChangedNotification extends QueuedNotification
{
    use BrandedMail;

    public function __construct(public Order $order)
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
            ->subject($this->title())
            ->greeting('مرحباً '.$notifiable->fullName())
            ->line($this->body());
    }

    public function toArray(object $notifiable): array
    {
        $stateKey = $this->order->state?->state;

        return array_merge(MemberStatusLabels::notificationState($stateKey, 'order'), [
            'type' => 'order_state_changed',
            'order_id' => $this->order->id,
            'reason' => $this->order->state_reason,
            'title' => $this->title(),
            'message' => $this->body(),
        ]);
    }

    private function title(): string
    {
        $state = $this->order->state?->state;

        return match ($state) {
            'pending' => 'طلبك '.MemberStatusLabels::order($state),
            'confirmed' => 'طلبك مؤكد وجاهز للاستلام',
            'delivered' => 'تم تسليم طلبك',
            'cancelled' => 'تم إلغاء طلبك',
            'rejected' => 'تم رفض طلبك',
            default => 'تحديث حالة الطلب إلى '.MemberStatusLabels::order($state),
        };
    }

    private function body(): string
    {
        $state = $this->order->state?->state;
        $label = MemberStatusLabels::order($state);
        $reason = $this->order->state_reason;

        $line = match ($state) {
            'pending' => 'طلبك رقم '.$this->order->id.' حالته: '.$label.'.',
            'confirmed' => 'تم تأكيد طلبك رقم '.$this->order->id.' وخصم النقاط. يرجى الحضور إلى المكتبة لاستلام الكتاب الورقي.',
            'delivered' => 'تم تسليم طلبك رقم '.$this->order->id.' في المكتبة.',
            'cancelled' => 'تم إلغاء طلبك رقم '.$this->order->id.' وإعادة النقاط ومخزون البيع إن وُجد خصم.',
            'rejected' => 'تم رفض طلبك رقم '.$this->order->id.' وإعادة النقاط ومخزون البيع إن وُجد خصم.',
            default => 'تم تحديث حالة طلبك رقم '.$this->order->id.' إلى: '.$label,
        };

        if (in_array($state, ['cancelled', 'rejected'], true) && $reason) {
            $line .= ' السبب: '.$reason;
        }

        return $line;
    }
}
