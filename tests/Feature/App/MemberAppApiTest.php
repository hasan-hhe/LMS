<?php

namespace Tests\Feature\App;

use App\Models\Borrowing;
use App\Models\LateFine;
use App\Models\TopUpCode;
use App\Models\User;
use App\Models\UserPoint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MemberAppApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_can_register_and_receives_token_and_empty_wallet(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'first_name' => 'أحمد',
            'last_name' => 'محمد',
            'email' => 'member@example.com',
            'phone' => '0999999999',
            'identity_number' => '1234567890',
            'adress' => 'دمشق',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertCreated()
            ->assertJsonPath('message', 'success')
            ->assertJsonStructure(['data' => ['user', 'token']]);
        $this->assertDatabaseHas('users', ['email' => 'member@example.com', 'role' => 'MEMBER']);
        $this->assertDatabaseHas('user_points', ['balance' => 0]);
    }

    public function test_public_opac_exposes_only_public_book_fields(): void
    {
        $authorId = DB::table('authers')->insertGetId(['firstname' => 'نجيب', 'lastname' => 'محفوظ', 'nationality' => 'مصري']);
        $categoryId = DB::table('catagories')->insertGetId(['title' => 'رواية', 'discription' => 'روايات']);
        $publisherId = DB::table('publishers')->insertGetId(['name' => 'دار النشر', 'location' => 'دمشق']);
        DB::table('books')->insert([
            'ISBN' => '9780000000001', 'auther_id' => $authorId, 'catagory_id' => $categoryId,
            'publisher_id' => $publisherId, 'title' => 'كتاب عام', 'discription' => 'وصف داخلي',
            'price' => 1000, 'price_points' => 10, 'amount' => 1, 'rate_avg' => 4.5,
            'cover_url' => 'cover.jpg', 'year_of_publishing' => '2026', 'number_edition' => '1',
        ]);

        $this->getJson('/api/v1/opac/books/9780000000001')
            ->assertOk()
            ->assertJsonPath('data.title', 'كتاب عام')
            ->assertJsonMissingPath('data.description')
            ->assertJsonMissingPath('data.publisher_id');
    }

    public function test_member_can_redeem_top_up_code(): void
    {
        $admin = User::factory()->create(['role' => 'ADMIN']);
        /** @var User $member */
        $member = User::factory()->create(['role' => 'MEMBER']);
        $code = TopUpCode::create(['code' => 'LMS-PTS-TEST1234', 'points_value' => 75, 'created_by' => $admin->id]);

        $this->actingAs($member)
            ->postJson('/api/v1/member/points/top-up', ['code' => $code->code])
            ->assertOk()
            ->assertJsonPath('data.balance', 75);

        $this->assertDatabaseHas('user_points', ['user_id' => $member->id, 'balance' => 75]);
    }

    public function test_member_can_pay_only_their_fine_with_points(): void
    {
        /** @var User $member */
        $member = User::factory()->create(['role' => 'MEMBER']);
        $librarian = User::factory()->create(['role' => 'LIBRARIAN']);
        UserPoint::create(['user_id' => $member->id, 'balance' => 100]);
        $instanceId = $this->createBookInstance();
        $borrowing = Borrowing::create([
            'member_id' => $member->id, 'librarian_id' => $librarian->id, 'book_instance_id' => $instanceId,
            'start_date' => now()->subDays(10), 'end_date' => now()->subDays(3), 'due_date' => now()->subDays(3),
            'borrowing_cast' => 0, 'is_paid' => false,
        ]);
        $fine = LateFine::create(['borrowing_id' => $borrowing->id, 'days_late' => 3, 'fine' => 500, 'fine_points' => 20]);

        $this->actingAs($member)
            ->putJson("/api/v1/member/fines/{$fine->id}/pay")
            ->assertOk()
            ->assertJsonPath('data.is_paid', true);
        $this->assertDatabaseHas('user_points', ['user_id' => $member->id, 'balance' => 80]);
    }

    public function test_forgot_password_is_non_enumerating_and_reset_token_works(): void
    {
        $member = User::factory()->create(['role' => 'MEMBER', 'email' => 'reset@example.com']);

        $this->postJson('/api/v1/auth/forgot-password', ['login' => $member->email])
            ->assertOk()
            ->assertJsonPath('message', 'success');
        $this->assertDatabaseHas('password_reset_tokens', ['email' => $member->email]);

        DB::table('password_reset_tokens')->where('email', $member->email)->update(['token' => Hash::make('known-token')]);
        $this->postJson('/api/v1/auth/reset-password', [
            'email' => $member->email, 'token' => 'known-token',
            'password' => 'new-password', 'password_confirmation' => 'new-password',
        ])->assertOk();
        $this->assertTrue(Hash::check('new-password', $member->fresh()->password_hash));

        $this->postJson('/api/v1/auth/forgot-password', ['login' => 'missing@example.com'])->assertOk();
    }

    private function createBookInstance(): int
    {
        $authorId = DB::table('authers')->insertGetId(['firstname' => 'A', 'lastname' => 'B', 'nationality' => 'SY']);
        $categoryId = DB::table('catagories')->insertGetId(['title' => 'C', 'discription' => 'D']);
        $publisherId = DB::table('publishers')->insertGetId(['name' => 'P', 'location' => 'L']);
        $stateId = DB::table('instance_states')->insertGetId(['state' => 'borrowed']);
        DB::table('books')->insert([
            'ISBN' => '9780000000002', 'auther_id' => $authorId, 'catagory_id' => $categoryId,
            'publisher_id' => $publisherId, 'title' => 'Book', 'discription' => 'Description',
            'price' => 1000, 'price_points' => 10, 'amount' => 1, 'rate_avg' => 0,
            'cover_url' => 'cover.jpg', 'year_of_publishing' => '2026', 'number_edition' => '1',
        ]);

        return DB::table('book_instances')->insertGetId([
            'book_ISBN' => '9780000000002', 'state_id' => $stateId, 'condition' => 'new',
        ]);
    }
}
