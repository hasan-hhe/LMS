<?php

namespace Tests\Feature\Dashboard;

use App\Models\Author;
use App\Models\Book;
use App\Models\BookInstance;
use App\Models\Borrowing;
use App\Models\Category;
use App\Models\InstanceState;
use App\Models\Publisher;
use App\Models\Reservation;
use App\Models\ReservationState;
use App\Models\User;
use App\Models\UserPoint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BorrowingControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $librarian;
    private User $member;
    private BookInstance $instance;

    protected function setUp(): void
    {
        parent::setUp();

        $this->librarian = User::factory()->create(['role' => 'LIBRARIAN', 'password_hash' => bcrypt('p')]);
        $this->member    = User::factory()->create([
            'role'               => 'MEMBER',
            'password_hash'      => bcrypt('p'),
            'participe_end_date' => now()->addYear(),
        ]);

        $availableState = InstanceState::create(['state' => 'available']);
        $borrowedState  = InstanceState::create(['state' => 'borrowed']);

        $author    = Author::create(['firstname' => 'م', 'lastname' => 'ن', 'nationality' => 'أ']);
        $category  = Category::create(['title' => 'عام', 'discription' => 'وصف']);
        $publisher = Publisher::create(['name' => 'نشر', 'location' => 'القاهرة']);

        $book = Book::create([
            'ISBN'               => '978-test-001',
            'auther_id'          => $author->id,
            'catagory_id'        => $category->id,
            'publisher_id'       => $publisher->id,
            'title'              => 'كتاب اختبار',
            'discription'        => 'وصف',
            'price'              => 20.0,
            'amount'             => 1,
            'rate_avg'           => 0,
            'cover_url' => '',
            'year_of_publishing' => '2020',
            'number_edition'     => '1',
        ]);

        $this->instance = BookInstance::create([
            'book_ISBN' => $book->ISBN,
            'state_id'  => $availableState->id,
            'condition' => 'new',
        ]);
    }

    public function test_checkout_creates_borrowing(): void
    {
        $token = $this->librarian->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/borrowings', [
                'member_id'        => $this->member->id,
                'book_instance_id' => $this->instance->id,
                'end_date'         => now()->addDays(14)->toDateString(),
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.member.id', $this->member->id);

        $this->assertDatabaseHas('borrowings', [
            'member_id'        => $this->member->id,
            'book_instance_id' => $this->instance->id,
        ]);
    }

    public function test_checkout_debits_book_borrow_points(): void
    {
        $this->instance->book->update(['borrow_points' => 8]);
        UserPoint::create(['user_id' => $this->member->id, 'balance' => 20]);

        $token = $this->librarian->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/borrowings', [
                'member_id' => $this->member->id,
                'book_instance_id' => $this->instance->id,
                'end_date' => now()->addDays(14)->toDateString(),
            ])
            ->assertStatus(201);

        $this->assertDatabaseHas('user_points', [
            'user_id' => $this->member->id,
            'balance' => 12,
        ]);
        $this->assertDatabaseHas('point_transactions', [
            'user_id' => $this->member->id,
            'points' => -8,
            'type' => 'spend',
        ]);
    }

    public function test_checkout_fails_when_borrow_points_exceed_balance(): void
    {
        $this->instance->book->update(['borrow_points' => 10]);
        UserPoint::create(['user_id' => $this->member->id, 'balance' => 3]);

        $token = $this->librarian->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/borrowings', [
                'member_id' => $this->member->id,
                'book_instance_id' => $this->instance->id,
                'end_date' => now()->addDays(14)->toDateString(),
            ])
            ->assertStatus(422)
            ->assertJsonPath('body', 'رصيد النقاط غير كافٍ لإتمام العملية');

        $this->assertDatabaseMissing('borrowings', [
            'member_id' => $this->member->id,
            'book_instance_id' => $this->instance->id,
        ]);
    }

    public function test_checkout_fails_if_instance_not_available(): void
    {
        $borrowedState = InstanceState::where('state', 'borrowed')->first();
        $this->instance->update(['state_id' => $borrowedState->id]);

        $token = $this->librarian->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/borrowings', [
                'member_id'        => $this->member->id,
                'book_instance_id' => $this->instance->id,
                'end_date'         => now()->addDays(14)->toDateString(),
            ])
            ->assertStatus(422);
    }

    public function test_checkout_fails_when_member_exceeds_limit(): void
    {
        $availableState = InstanceState::where('state', 'available')->first();

        Borrowing::create([
            'member_id'        => $this->member->id,
            'librarian_id'     => $this->librarian->id,
            'book_instance_id' => $this->instance->id,
            'start_date'       => now(),
            'end_date'         => now()->addDays(14),
            'due_date'         => now()->addDays(14),
            'borrowing_cast'   => 0,
            'is_paid'          => false,
        ]);

        $token = $this->librarian->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/borrowings', [
                'member_id'        => $this->member->id,
                'book_instance_id' => $this->instance->id,
                'end_date'         => now()->addDays(14)->toDateString(),
            ])
            ->assertStatus(422);
    }

    public function test_return_book_updates_borrowing(): void
    {
        $borrowing = Borrowing::create([
            'member_id'        => $this->member->id,
            'librarian_id'     => $this->librarian->id,
            'book_instance_id' => $this->instance->id,
            'start_date'       => now(),
            'end_date'         => now()->addDays(14),
            'due_date'         => now()->addDays(14),
            'borrowing_cast'   => 0,
            'is_paid'          => false,
        ]);

        $token = $this->librarian->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson("/api/v1/borrowings/{$borrowing->id}/return");

        $response->assertStatus(200)
            ->assertJsonPath('data.is_returned', true);
    }

    public function test_return_damaged_creates_replacement_fine(): void
    {
        InstanceState::create(['state' => 'damaged']);
        $this->instance->book?->update(['price_points' => 15]);

        $borrowing = Borrowing::create([
            'member_id'        => $this->member->id,
            'librarian_id'     => $this->librarian->id,
            'book_instance_id' => $this->instance->id,
            'start_date'       => now(),
            'end_date'         => now()->addDays(14),
            'due_date'         => now()->addDays(14),
            'borrowing_cast'   => 0,
            'is_paid'          => false,
        ]);

        $token = $this->librarian->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson("/api/v1/borrowings/{$borrowing->id}/return", ['outcome' => 'damaged'])
            ->assertStatus(200)
            ->assertJsonPath('data.damage_fine.type', 'damage')
            ->assertJsonPath('data.damage_fine.fine_points', 15);

        $this->assertDatabaseHas('late_fines', [
            'borrowing_id' => $borrowing->id,
            'type' => 'damage',
            'fine_points' => 15,
        ]);
        $this->assertDatabaseHas('book_instances', [
            'id' => $this->instance->id,
            'state_id' => InstanceState::where('state', 'damaged')->value('id'),
        ]);
    }

    public function test_damage_return_waives_remaining_late_fine(): void
    {
        InstanceState::create(['state' => 'damaged']);
        $borrowedState = InstanceState::where('state', 'borrowed')->first();
        $this->instance->update(['state_id' => $borrowedState->id]);
        $this->instance->book?->update(['price' => 20, 'price_points' => 10]);

        $borrowing = Borrowing::create([
            'member_id' => $this->member->id,
            'librarian_id' => $this->librarian->id,
            'book_instance_id' => $this->instance->id,
            'start_date' => now()->subDays(20),
            'end_date' => now()->subDays(5),
            'due_date' => now()->subDays(5),
            'borrowing_cast' => 0,
            'is_paid' => false,
        ]);

        \App\Models\LateFine::create([
            'borrowing_id' => $borrowing->id,
            'type' => 'late',
            'days_late' => 5,
            'fine' => 2.5,
            'fine_points' => 5,
            'is_paid' => false,
        ]);

        $token = $this->librarian->createToken('test')->plainTextToken;
        $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson("/api/v1/borrowings/{$borrowing->id}/return", ['outcome' => 'damaged'])
            ->assertStatus(200);

        $this->assertDatabaseHas('late_fines', [
            'borrowing_id' => $borrowing->id,
            'type' => 'late',
            'is_paid' => true,
            'paid_via' => 'waived',
        ]);
        $this->assertDatabaseHas('late_fines', [
            'borrowing_id' => $borrowing->id,
            'type' => 'damage',
            'fine_points' => 10,
        ]);
    }

    public function test_admin_can_extend_after_member_extension(): void
    {
        $borrowedState = InstanceState::where('state', 'borrowed')->first();
        $this->instance->update(['state_id' => $borrowedState->id]);
        UserPoint::create(['user_id' => $this->member->id, 'balance' => 50]);

        $borrowing = Borrowing::create([
            'member_id' => $this->member->id,
            'librarian_id' => $this->librarian->id,
            'book_instance_id' => $this->instance->id,
            'start_date' => now(),
            'end_date' => now()->addDays(7),
            'due_date' => now()->addDays(7),
            'borrowing_cast' => 0,
            'is_paid' => false,
        ]);

        $token = $this->librarian->createToken('test')->plainTextToken;
        $firstEnd = now()->addDays(14)->toDateString();
        $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson("/api/v1/borrowings/{$borrowing->id}/extend", [
                'new_end_date' => $firstEnd,
            ])
            ->assertStatus(200);

        $adminEnd = now()->addDays(21)->toDateString();
        $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson("/api/v1/borrowings/{$borrowing->id}/extend", [
                'new_end_date' => $adminEnd,
                'administrative' => true,
                'cause' => 'تمديد إداري',
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.end_date', $adminEnd);
    }

    public function test_extension_quote_returns_points(): void
    {
        $borrowedState = InstanceState::where('state', 'borrowed')->first();
        $this->instance->update(['state_id' => $borrowedState->id]);

        $borrowing = Borrowing::create([
            'member_id' => $this->member->id,
            'librarian_id' => $this->librarian->id,
            'book_instance_id' => $this->instance->id,
            'start_date' => now(),
            'end_date' => now()->addDays(7),
            'due_date' => now()->addDays(7),
            'borrowing_cast' => 0,
            'is_paid' => false,
        ]);

        $token = $this->librarian->createToken('test')->plainTextToken;
        $newEnd = now()->addDays(14)->toDateString();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson("/api/v1/borrowings/{$borrowing->id}/extension-quote?new_end_date={$newEnd}")
            ->assertStatus(200)
            ->assertJsonPath('data.can_extend', true)
            ->assertJsonPath('data.days', 7);
    }

    public function test_restore_returns_damaged_copy_to_available(): void
    {
        $damaged = InstanceState::create(['state' => 'damaged']);
        $this->instance->update(['state_id' => $damaged->id]);

        $token = $this->librarian->createToken('test')->plainTextToken;
        $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson("/api/v1/book-instances/{$this->instance->id}/restore")
            ->assertStatus(200)
            ->assertJsonPath('data.state.state', 'available');
    }

    public function test_index_returns_borrowings_list(): void
    {
        $token = $this->librarian->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/borrowings')
            ->assertStatus(200)
            ->assertJsonStructure(['data', 'meta']);
    }

    public function test_index_filters_active_borrowing_by_instance(): void
    {
        $borrowing = Borrowing::create([
            'member_id'        => $this->member->id,
            'librarian_id'     => $this->librarian->id,
            'book_instance_id' => $this->instance->id,
            'start_date'       => now(),
            'end_date'         => now()->addDays(14),
            'due_date'         => now()->addDays(14),
            'borrowing_cast'   => 0,
            'is_paid'          => false,
        ]);

        $token = $this->librarian->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/borrowings?book_instance_id='.$this->instance->id.'&is_returned=false')
            ->assertStatus(200)
            ->assertJsonPath('data.0.id', $borrowing->id);
    }

    public function test_checkout_fails_when_copy_is_reserved_for_the_same_member(): void
    {
        $reservedState = InstanceState::create(['state' => 'reserved']);
        $this->instance->update(['state_id' => $reservedState->id]);
        $pending = ReservationState::create(['state' => 'pending']);

        Reservation::create([
            'user_id'          => $this->member->id,
            'book_instance_id' => $this->instance->id,
            'state_id'         => $pending->id,
            'cause'            => '',
            'reserved_at'      => now(),
        ]);

        $token = $this->librarian->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/borrowings', [
                'member_id'        => $this->member->id,
                'book_instance_id' => $this->instance->id,
                'end_date'         => now()->addDays(14)->toDateString(),
            ])
            ->assertStatus(422);
    }

    public function test_checkout_fails_when_copy_is_reserved_for_another_member(): void
    {
        $otherMember = User::factory()->create([
            'role'               => 'MEMBER',
            'password_hash'      => bcrypt('p'),
            'participe_end_date' => now()->addYear(),
        ]);
        $reservedState = InstanceState::create(['state' => 'reserved']);
        $this->instance->update(['state_id' => $reservedState->id]);
        $pending = ReservationState::create(['state' => 'pending']);

        Reservation::create([
            'user_id'          => $otherMember->id,
            'book_instance_id' => $this->instance->id,
            'state_id'         => $pending->id,
            'cause'            => '',
            'reserved_at'      => now(),
        ]);

        $token = $this->librarian->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/borrowings', [
                'member_id'        => $this->member->id,
                'book_instance_id' => $this->instance->id,
                'end_date'         => now()->addDays(14)->toDateString(),
            ])
            ->assertStatus(422);
    }

    public function test_return_keeps_copy_reserved_when_another_hold_exists(): void
    {
        $borrowedState = InstanceState::where('state', 'borrowed')->first();
        $this->instance->update(['state_id' => $borrowedState->id]);

        $borrowing = Borrowing::create([
            'member_id'        => $this->member->id,
            'librarian_id'     => $this->librarian->id,
            'book_instance_id' => $this->instance->id,
            'start_date'       => now()->subDays(7),
            'end_date'         => now()->addDays(7),
            'due_date'         => now()->addDays(7),
            'borrowing_cast'   => 0,
            'is_paid'          => false,
        ]);

        $pending = ReservationState::create(['state' => 'pending']);
        InstanceState::create(['state' => 'reserved']);

        Reservation::create([
            'user_id'          => $this->member->id,
            'book_instance_id' => $this->instance->id,
            'state_id'         => $pending->id,
            'cause'            => '',
            'reserved_at'      => now(),
        ]);

        $token = $this->librarian->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson("/api/v1/borrowings/{$borrowing->id}/return")
            ->assertStatus(200);

        $this->assertDatabaseHas('book_instances', [
            'id'       => $this->instance->id,
            'state_id' => InstanceState::where('state', 'reserved')->value('id'),
        ]);
    }
}
