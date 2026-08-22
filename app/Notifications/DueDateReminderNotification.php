<?php

namespace App\Notifications;

use App\Models\Borrowing;
use App\Notifications\Concerns\BrandedMail;
use App\Support\MemberStatusLabels;
use Illuminate\Notifications\Messages\MailMessage;

class DueDateReminderNotification extends QueuedNotification
{
    use BrandedMail;

    public function __construct(public Borrowing $borrowing)
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
            ->subject('تذكير بموعد إعادة الكتاب')
            ->greeting('مرحباً '.$notifiable->fullName())
            ->line('موعد إعادة كتاب "'.$this->borrowing->bookInstance?->book?->title.'" هو غداً.')
            ->line('يرجى إعادة الكتاب في الموعد لتجنب الغرامة.');
    }

    public function toArray(object $notifiable): array
    {
        $overdue = $this->borrowing->isOverdue();
        $statusKey = $this->borrowing->isReturned() ? 'returned' : ($overdue ? 'overdue' : 'active');

        return array_merge(MemberStatusLabels::notificationState($statusKey, 'borrowing'), [
            'type' => 'due_date_reminder',
            'borrowing_id' => $this->borrowing->id,
            'book_title' => $this->borrowing->bookInstance?->book?->title,
            'due_date' => $this->borrowing->end_date?->toDateString(),
            'title' => 'تذكير بموعد إعادة الكتاب',
            'message' => 'موعد إعادة الكتاب غداً. حالة الاستعارة: '.MemberStatusLabels::borrowingStatus($statusKey),
        ]);
    }
}
