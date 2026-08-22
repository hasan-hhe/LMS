<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use App\Notifications\StaffNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\SendQueuedNotifications;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class QueuedMailNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_forgot_password_queues_the_notification_instead_of_sending_mail_inline(): void
    {
        Mail::fake();
        Queue::fake();

        $member = User::factory()->create(['role' => 'MEMBER', 'email' => 'reset@example.com']);

        $this->postJson('/api/v1/auth/forgot-password', ['login' => $member->email])
            ->assertOk()
            ->assertJsonPath('message', 'success');

        Queue::assertPushed(SendQueuedNotifications::class, function (SendQueuedNotifications $job) {
            return $job->notification instanceof ResetPasswordNotification;
        });
        Mail::assertNothingSent();
    }

    public function test_staff_notification_with_email_is_queued_instead_of_sending_mail_inline(): void
    {
        Mail::fake();
        Queue::fake();

        $librarian = User::factory()->create(['role' => 'LIBRARIAN']);
        User::factory()->create(['role' => 'MEMBER']);

        $this->withHeader('Authorization', 'Bearer '.$librarian->createToken('test')->plainTextToken)
            ->postJson('/api/v1/notifications/send', [
                'title' => 'صيانة المكتبة',
                'body' => 'المكتبة مغلقة غداً',
                'audience' => 'members',
                'send_email' => true,
            ])
            ->assertCreated()
            ->assertJsonPath('data.sent_count', 1);

        Queue::assertPushed(SendQueuedNotifications::class, function (SendQueuedNotifications $job) {
            return $job->notification instanceof StaffNotification
                && $job->notification->sendEmail === true;
        });
        Mail::assertNothingSent();
    }
}
