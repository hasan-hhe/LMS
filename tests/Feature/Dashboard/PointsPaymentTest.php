<?php

namespace Tests\Feature\Dashboard;

use App\Models\User;
use App\Models\UserPoint;
use App\Services\PointService;
use App\Services\TopUpCodeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PointsPaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_credit_debit_and_insufficient_balance_are_recorded_atomically(): void
    {
        $member = User::factory()->create(['role' => 'MEMBER']);
        $service = app(PointService::class);

        $service->credit($member->id, 100, 'top_up');
        $service->debit($member->id, 35, 'spend');

        $this->assertSame(65, $service->getBalance($member->id));
        $this->assertDatabaseHas('point_transactions', ['user_id' => $member->id, 'points' => -35, 'type' => 'spend']);

        $this->expectExceptionMessage('رصيد النقاط غير كافٍ');
        $service->debit($member->id, 66, 'spend');
    }

    public function test_top_up_code_can_only_be_redeemed_once(): void
    {
        $admin = User::factory()->create(['role' => 'ADMIN']);
        $member = User::factory()->create(['role' => 'MEMBER']);
        $service = app(TopUpCodeService::class);
        $code = $service->generateBatch(1, 50, null, $member->id, $admin->id)[0];

        $service->redeem($code->code, $member->id);

        $this->assertSame(50, UserPoint::where('user_id', $member->id)->value('balance'));
        $this->expectExceptionMessage('تم استخدام رمز الشحن مسبقاً');
        $service->redeem($code->code, $member->id);
    }

    public function test_admin_can_read_member_balance(): void
    {
        /** @var User $admin */
        $admin = User::factory()->create(['role' => 'ADMIN']);
        $member = User::factory()->create(['role' => 'MEMBER']);
        UserPoint::create(['user_id' => $member->id, 'balance' => 80]);

        $this->actingAs($admin)
            ->getJson('/api/v1/points/balance?member_id='.$member->id)
            ->assertOk()
            ->assertJsonPath('data.balance', 80);
    }

    public function test_librarian_can_top_up_by_code_or_direct_points_and_read_rate(): void
    {
        /** @var User $librarian */
        $librarian = User::factory()->create(['role' => 'LIBRARIAN']);
        $member = User::factory()->create(['role' => 'MEMBER']);
        UserPoint::create(['user_id' => $member->id, 'balance' => 10]);
        $code = app(TopUpCodeService::class)->generateBatch(1, 40, null, null, $librarian->id)[0];

        $this->actingAs($librarian)
            ->getJson('/api/v1/points/settings')
            ->assertOk()
            ->assertJsonPath('data.syp_per_point', '100');

        $this->actingAs($librarian)
            ->postJson('/api/v1/points/top-up', [
                'code' => $code->code,
                'member_id' => $member->id,
            ])
            ->assertOk();
        $this->assertSame(50, UserPoint::where('user_id', $member->id)->value('balance'));

        $this->actingAs($librarian)
            ->postJson('/api/v1/points/adjust', [
                'member_id' => $member->id,
                'points' => 25,
                'note' => 'شحن مباشر من أمين المكتبة',
            ])
            ->assertOk()
            ->assertJsonPath('data.points', 25);
        $this->assertSame(75, UserPoint::where('user_id', $member->id)->value('balance'));
    }

    public function test_librarian_cannot_change_point_settings_or_generate_codes(): void
    {
        /** @var User $librarian */
        $librarian = User::factory()->create(['role' => 'LIBRARIAN']);

        $this->actingAs($librarian)
            ->putJson('/api/v1/points/settings', ['syp_per_point' => 50])
            ->assertForbidden();

        $this->actingAs($librarian)
            ->postJson('/api/v1/top-up-codes/generate', [
                'count' => 1,
                'points_value' => 10,
            ])
            ->assertForbidden();
    }
}
