<?php

namespace Tests\Feature\App;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;
use Tests\TestCase;

class NotificationApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_librarian_can_list_own_notifications(): void
    {
        /** @var User $librarian */
        $librarian = User::factory()->create(['role' => 'LIBRARIAN']);
        DatabaseNotification::create([
            'id' => (string) Str::uuid(),
            'type' => 'App\\Notifications\\ResetPasswordNotification',
            'notifiable_type' => User::class,
            'notifiable_id' => $librarian->id,
            'data' => ['title' => 'تنبيه', 'message' => 'رسالة اختبار'],
        ]);

        $this->actingAs($librarian)
            ->getJson('/api/v1/notifications')
            ->assertOk()
            ->assertJsonPath('message', 'success')
            ->assertJsonPath('data.0.message', 'رسالة اختبار');
    }

    public function test_librarian_can_mark_all_notifications_read(): void
    {
        /** @var User $librarian */
        $librarian = User::factory()->create(['role' => 'LIBRARIAN']);
        DatabaseNotification::create([
            'id' => (string) Str::uuid(),
            'type' => 'App\\Notifications\\ResetPasswordNotification',
            'notifiable_type' => User::class,
            'notifiable_id' => $librarian->id,
            'data' => ['message' => 'رسالة اختبار'],
        ]);

        $this->actingAs($librarian)
            ->postJson('/api/v1/notifications/read-all')
            ->assertOk()
            ->assertJsonPath('message', 'success');

        $this->assertDatabaseMissing('notifications', [
            'notifiable_id' => $librarian->id,
            'read_at' => null,
        ]);
    }

    public function test_notification_list_localizes_english_state_names(): void
    {
        /** @var User $member */
        $member = User::factory()->create(['role' => 'MEMBER']);
        DatabaseNotification::create([
            'id' => (string) Str::uuid(),
            'type' => 'App\\Notifications\\OrderStateChangedNotification',
            'notifiable_type' => User::class,
            'notifiable_id' => $member->id,
            'data' => [
                'type' => 'order_state_changed',
                'state' => 'confirmed',
                'title' => 'تحديث حالة الطلب',
                'message' => 'تم تحديث الحالة',
            ],
        ]);

        $this->actingAs($member)
            ->getJson('/api/v1/member/notifications')
            ->assertOk()
            ->assertJsonPath('data.data.0.data.state', 'مؤكد')
            ->assertJsonPath('data.data.0.data.state_label', 'مؤكد')
            ->assertJsonPath('data.data.0.data.state_key', 'confirmed');
    }

    public function test_librarian_is_still_forbidden_from_member_self_service(): void
    {
        /** @var User $librarian */
        $librarian = User::factory()->create(['role' => 'LIBRARIAN']);

        $this->actingAs($librarian)
            ->getJson('/api/v1/member/points/balance')
            ->assertForbidden()
            ->assertJsonPath('body', 'ليس لديك الصلاحية للوصول لهذا المورد');
    }
}
