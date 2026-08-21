<?php

namespace Tests\Feature\Dashboard;

use App\Models\Author;
use App\Models\Book;
use App\Models\BookInstance;
use App\Models\Category;
use App\Models\InstanceState;
use App\Models\Publisher;
use App\Models\Reservation;
use App\Models\ReservationState;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ReservationControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $librarian;
    private User $member;
    private BookInstance $instance;
    private InstanceState $availableState;
    private ReservationState $pendingState;
    private ReservationState $cancelledState;

    protected function setUp(): void
    {
        parent::setUp();

        $this->librarian      = User::factory()->create(['role' => 'LIBRARIAN', 'password_hash' => bcrypt('p')]);
        $this->member         = User::factory()->create(['role' => 'MEMBER', 'password_hash' => bcrypt('p')]);
        $this->pendingState   = ReservationState::create(['state' => 'pending']);
        $this->cancelledState = ReservationState::create(['state' => 'cancelled']);
        ReservationState::create(['state' => 'ready']);
        ReservationState::create(['state' => 'fulfilled']);

        $this->availableState = InstanceState::create(['state' => 'available']);
        InstanceState::create(['state' => 'reserved']);
        InstanceState::create(['state' => 'borrowed']);
        $author   = Author::create(['firstname' => 'م', 'lastname' => 'ن', 'nationality' => 'أ']);
        $category = Category::create(['title' => 'عام', 'discription' => 'وصف']);
        $pub      = Publisher::create(['name' => 'نشر', 'location' => 'مكان']);
        $book     = Book::create([
            'ISBN' => '978-res-test', 'auther_id' => $author->id, 'catagory_id' => $category->id,
            'publisher_id' => $pub->id, 'title' => 'ك', 'discription' => 'و', 'price' => 20,
            'amount' => 1, 'rate_avg' => 0, 'cover_url' => '', 'year_of_publishing' => '2020', 'number_edition' => '1',
        ]);

        $this->instance = BookInstance::create(['book_ISBN' => $book->ISBN, 'state_id' => $this->availableState->id, 'condition' => 'new']);
    }

    public function test_store_creates_reservation(): void
    {
        $token = $this->librarian->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/reservations', [
                'user_id'          => $this->member->id,
                'book_instance_id' => $this->instance->id,
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('reservations', [
            'user_id'          => $this->member->id,
            'book_instance_id' => $this->instance->id,
        ]);
    }

    public function test_store_fails_with_duplicate_reservation(): void
    {
        Reservation::create([
            'user_id'          => $this->member->id,
            'book_instance_id' => $this->instance->id,
            'state_id'         => $this->pendingState->id,
            'cause' => '',
            'reserved_at'      => now(),
        ]);

        $token = $this->librarian->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/reservations', [
                'user_id'          => $this->member->id,
                'book_instance_id' => $this->instance->id,
            ])
            ->assertStatus(422);
    }

    public function test_cancel_changes_reservation_state(): void
    {
        $reservation = Reservation::create([
            'user_id'          => $this->member->id,
            'book_instance_id' => $this->instance->id,
            'state_id'         => $this->pendingState->id,
            'cause' => '',
            'reserved_at'      => now(),
        ]);

        $token = $this->librarian->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson("/api/v1/reservations/{$reservation->id}/cancel");

        $response->assertStatus(200);
        $this->assertDatabaseHas('reservations', [
            'id'       => $reservation->id,
            'state_id' => $this->cancelledState->id,
        ]);
    }

    public function test_index_returns_reservations(): void
    {
        $token = $this->librarian->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/reservations')
            ->assertStatus(200)
            ->assertJsonStructure(['data', 'meta']);
    }

    public function test_index_filters_reservations_by_state(): void
    {
        Reservation::create([
            'user_id'          => $this->member->id,
            'book_instance_id' => $this->instance->id,
            'state_id'         => $this->pendingState->id,
            'cause'            => '',
            'reserved_at'      => now(),
        ]);

        $token = $this->librarian->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/reservations?state=pending')
            ->assertStatus(200)
            ->assertJsonPath('data.0.user.id', $this->member->id);
    }

    public function test_store_fails_when_member_already_reserved_same_title(): void
    {
        $secondCopy = BookInstance::create([
            'book_ISBN' => $this->instance->book_ISBN,
            'state_id' => $this->instance->state_id,
            'condition' => 'new',
        ]);

        Reservation::create([
            'user_id' => $this->member->id,
            'book_instance_id' => $this->instance->id,
            'state_id' => $this->pendingState->id,
            'cause' => '',
            'reserved_at' => now(),
        ]);

        $token = $this->librarian->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/reservations', [
                'user_id' => $this->member->id,
                'book_instance_id' => $secondCopy->id,
            ])
            ->assertStatus(422)
            ->assertJsonPath('body', 'لديك حجز نشط مسبق لنفس الكتاب');
    }

    public function test_store_fails_when_member_exceeds_active_limit(): void
    {
        $book = Book::create([
            'ISBN' => '978-limit-1',
            'auther_id' => $this->instance->book->auther_id,
            'catagory_id' => $this->instance->book->catagory_id,
            'publisher_id' => $this->instance->book->publisher_id,
            'title' => 'ك1',
            'discription' => 'و',
            'price' => 20,
            'amount' => 1,
            'rate_avg' => 0,
            'cover_url' => '',
            'year_of_publishing' => '2020',
            'number_edition' => '1',
        ]);
        $copy = BookInstance::create([
            'book_ISBN' => $book->ISBN,
            'state_id' => $this->instance->state_id,
            'condition' => 'new',
        ]);
        Reservation::create([
            'user_id' => $this->member->id,
            'book_instance_id' => $copy->id,
            'state_id' => $this->pendingState->id,
            'cause' => '',
            'reserved_at' => now(),
        ]);

        $token = $this->librarian->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/reservations', [
                'user_id' => $this->member->id,
                'book_instance_id' => $this->instance->id,
            ])
            ->assertStatus(422)
            ->assertJsonPath('body', 'مسموح بحجز واحد فقط حتى ينتهي الحجز الحالي');
    }

    public function test_mark_ready_sets_pickup_deadline(): void
    {
        Notification::fake();

        $reservation = Reservation::create([
            'user_id' => $this->member->id,
            'book_instance_id' => $this->instance->id,
            'state_id' => $this->pendingState->id,
            'cause' => '',
            'reserved_at' => now(),
        ]);

        $this->instance->update([
            'state_id' => InstanceState::where('state', 'reserved')->value('id'),
        ]);

        $token = $this->librarian->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson("/api/v1/reservations/{$reservation->id}/ready")
            ->assertStatus(200)
            ->assertJsonPath('data.id', $reservation->id);

        $this->assertNotNull($reservation->fresh()->expires_at);
    }

    public function test_expire_command_cancels_unclaimed_ready_hold_and_releases_copy(): void
    {
        Notification::fake();

        $readyState = ReservationState::where('state', 'ready')->first();

        $expired = Reservation::create([
            'user_id' => $this->member->id,
            'book_instance_id' => $this->instance->id,
            'state_id' => $readyState->id,
            'cause' => '',
            'reserved_at' => now()->subDays(3),
            'notified_at' => now()->subDays(3),
            'expires_at' => now()->subHour(),
        ]);

        $this->instance->update([
            'state_id' => InstanceState::where('state', 'reserved')->value('id'),
        ]);

        $this->artisan('reservations:expire-unclaimed')->assertSuccessful();

        $this->assertDatabaseHas('reservations', [
            'id' => $expired->id,
            'state_id' => $this->cancelledState->id,
        ]);
        $this->assertDatabaseHas('book_instances', [
            'id' => $this->instance->id,
            'state_id' => $this->availableState->id,
        ]);
    }

    public function test_fulfill_creates_borrowing_and_marks_copy_borrowed(): void
    {
        $reservation = Reservation::create([
            'user_id' => $this->member->id,
            'book_instance_id' => $this->instance->id,
            'state_id' => $this->pendingState->id,
            'cause' => '',
            'reserved_at' => now(),
        ]);

        $this->instance->update([
            'state_id' => InstanceState::where('state', 'reserved')->value('id'),
        ]);

        $token = $this->librarian->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson("/api/v1/reservations/{$reservation->id}/fulfill")
            ->assertStatus(200)
            ->assertJsonPath('data.id', $reservation->id)
            ->assertJsonPath('data.borrowing.member.id', $this->member->id);

        $this->assertDatabaseHas('reservations', [
            'id' => $reservation->id,
            'state_id' => ReservationState::where('state', 'fulfilled')->value('id'),
        ]);
        $this->assertDatabaseHas('borrowings', [
            'member_id' => $this->member->id,
            'book_instance_id' => $this->instance->id,
        ]);
        $this->assertDatabaseHas('book_instances', [
            'id' => $this->instance->id,
            'state_id' => InstanceState::where('state', 'borrowed')->value('id'),
        ]);
    }

    public function test_store_fails_when_copy_is_borrowed(): void
    {
        $this->instance->update([
            'state_id' => InstanceState::where('state', 'borrowed')->value('id'),
        ]);

        $token = $this->librarian->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/reservations', [
                'user_id' => $this->member->id,
                'book_instance_id' => $this->instance->id,
            ])
            ->assertStatus(422)
            ->assertJsonPath('body', 'لا يمكن حجز نسخة مستعارة حالياً');
    }
}
