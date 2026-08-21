<?php

namespace Tests\Feature\App;

use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use App\Services\AblyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RealtimeNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_request_realtime_token(): void
    {
        $this->getJson('/api/v1/realtime/token')
            ->assertUnauthorized();
    }

    public function test_member_receives_realtime_token_payload(): void
    {
        /** @var User $member */
        $member = User::factory()->create(['role' => 'MEMBER']);

        $this->mock(AblyService::class, function ($mock) use ($member) {
            $mock->shouldReceive('isEnabled')->andReturn(true);
            $mock->shouldReceive('createUserTokenRequest')
                ->once()
                ->with($member->id)
                ->andReturn([
                    'token_request' => [
                        'keyName' => 'app.key',
                        'ttl' => 3600000,
                        'capability' => '{"user:'.$member->id.'":["subscribe"]}',
                        'clientId' => (string) $member->id,
                        'timestamp' => 1710000000000,
                        'nonce' => 'nonce-test',
                        'mac' => 'mac-test',
                    ],
                    'channel' => 'user:'.$member->id,
                    'event' => 'notification',
                ]);
        });

        $this->actingAs($member)
            ->getJson('/api/v1/realtime/token')
            ->assertOk()
            ->assertJsonPath('message', 'success')
            ->assertJsonPath('data.channel', 'user:'.$member->id)
            ->assertJsonPath('data.event', 'notification')
            ->assertJsonPath('data.token_request.clientId', (string) $member->id);
    }

    public function test_realtime_token_is_unavailable_when_ably_is_disabled(): void
    {
        /** @var User $member */
        $member = User::factory()->create(['role' => 'MEMBER']);

        $this->mock(AblyService::class, function ($mock) {
            $mock->shouldReceive('isEnabled')->andReturn(false);
        });

        $this->actingAs($member)
            ->getJson('/api/v1/realtime/token')
            ->assertStatus(503)
            ->assertJsonPath('body', 'خدمة الإشعارات اللحظية غير مفعّلة');
    }

    public function test_notification_is_published_to_the_user_ably_channel(): void
    {
        /** @var User $member */
        $member = User::factory()->create(['role' => 'MEMBER']);

        $this->mock(AblyService::class, function ($mock) use ($member) {
            $mock->shouldReceive('isEnabled')->andReturn(true);
            $mock->shouldReceive('publishUserNotification')
                ->once()
                ->withArgs(function (int $userId, array $payload) use ($member) {
                    return $userId === $member->id
                        && ($payload['data']['type'] ?? null) === 'password_reset'
                        && ! empty($payload['id']);
                });
        });

        $member->notify(new ResetPasswordNotification('token-test'));
    }
}
