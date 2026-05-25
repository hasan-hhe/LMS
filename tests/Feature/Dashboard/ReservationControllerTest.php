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
use Tests\TestCase;

class ReservationControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $librarian;
    private User $member;
    private BookInstance $instance;
    private ReservationState $pendingState;
    private ReservationState $cancelledState;

    protected function setUp(): void
    {
        parent::setUp();

        $this->librarian      = User::factory()->create(['role' => 'LIBRARIAN', 'password_hash' => bcrypt('p')]);
        $this->member         = User::factory()->create(['role' => 'MEMBER', 'password_hash' => bcrypt('p')]);
        $this->pendingState   = ReservationState::create(['state' => 'pending']);
        $this->cancelledState = ReservationState::create(['state' => 'cancelled']);

        $state    = InstanceState::create(['state' => 'borrowed']);
        $author   = Author::create(['firstname' => 'م', 'lastname' => 'ن', 'nationality' => 'أ']);
        $category = Category::create(['title' => 'عام', 'discription' => 'وصف']);
        $pub      = Publisher::create(['name' => 'نشر', 'location' => 'مكان']);
        $book     = Book::create([
            'ISBN' => '978-res-test', 'auther_id' => $author->id, 'catagory_id' => $category->id,
            'publisher_id' => $pub->id, 'title' => 'ك', 'discription' => 'و', 'price' => 20,
            'amount' => 1, 'rate_avg' => 0, 'cover_url' => null, 'year_of_publishing' => '2020', 'number_edition' => '1',
        ]);

        $this->instance = BookInstance::create(['book_ISBN' => $book->ISBN, 'state_id' => $state->id, 'condition' => 'new']);
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
            'cause'            => null,
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
            'cause'            => null,
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
}
