<?php

namespace App\Notifications;

use App\Notifications\Concerns\BrandedMail;
use Illuminate\Notifications\Messages\MailMessage;

class ResetPasswordNotification extends QueuedNotification
{
    use BrandedMail;

    public function __construct(public string $token)
    {
        parent::__construct();
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database', 'ably'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = url('/reset-password?email='.urlencode($notifiable->email).'&token='.$this->token);

        return $this->mail()
            ->subject('استعادة كلمة المرور')
            ->greeting('مرحباً '.$notifiable->fullName())
            ->line('طلبت إعادة تعيين كلمة المرور لحسابك.')
            ->line('رمز الاستعادة: '.$this->token)
            ->action('إعادة تعيين كلمة المرور', $url)
            ->line('ينتهي صلاحية الرمز خلال ساعة واحدة وإذا لم تطلب ذلك فتجاهل الرسالة.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'password_reset',
            'title' => 'استعادة كلمة المرور',
            'message' => 'تم إنشاء رمز استعادة كلمة المرور',
        ];
    }
}
