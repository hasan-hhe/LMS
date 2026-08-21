<?php

namespace Tests\Feature\Dashboard;

use App\Models\User;
use App\Notifications\StaffNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class NotificationControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $librarian;

    protected function setUp(): void
    {
        parent::setUp();
        $this->librarian = User::factory()->create(['role' => 'LIBRARIAN']);
    }

    private function authHeaders(): array
    {
        return ['Authorization' => 'Bearer '.$this->librarian->createToken('test')->plainTextToken];
    }

    public function test_librarian_can_send_notification_to_all_members(): void
    {
        Notification::fake();
        $first = User::factory()->create(['role' => 'MEMBER']);
        $second = User::factory()->create(['role' => 'MEMBER']);
        User::factory()->create(['role' => 'ADMIN']);

        $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/notifications/send', [
                'title' => 'صيانة المكتبة',
                'body' => 'المكتبة مغلقة غداً',
                'audience' => 'members',
            ])
            ->assertCreated()
            ->assertJsonPath('message', 'success')
            ->assertJsonPath('data.sent_count', 2);

        Notification::assertSentTo([$first, $second], StaffNotification::class);
        Notification::assertNotSentTo($this->librarian, StaffNotification::class);
    }

    public function test_librarian_can_send_notification_to_selected_users(): void
    {
        Notification::fake();
        $member = User::factory()->create(['role' => 'MEMBER']);
        $other = User::factory()->create(['role' => 'MEMBER']);

        $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/notifications/send', [
                'title' => 'تنبيه شخصي',
                'body' => 'يرجى مراجعة مكتبتك',
                'audience' => 'selected',
                'user_ids' => [$member->id],
            ])
            ->assertCreated()
            ->assertJsonPath('data.sent_count', 1);

        Notification::assertSentTo($member, StaffNotification::class);
        Notification::assertNotSentTo($other, StaffNotification::class);
    }

    public function test_selected_audience_requires_user_ids(): void
    {
        $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/notifications/send', [
                'title' => 'تنبيه',
                'body' => 'نص',
                'audience' => 'selected',
            ])
            ->assertStatus(422)
            ->assertJsonPath('body', 'بيانات الإشعار غير صحيحة');
    }

    public function test_member_cannot_send_dashboard_notification(): void
    {
        $member = User::factory()->create(['role' => 'MEMBER']);
        $token = $member->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/notifications/send', [
                'title' => 'تنبيه',
                'body' => 'نص',
                'audience' => 'members',
            ])
            ->assertForbidden();
    }
}
