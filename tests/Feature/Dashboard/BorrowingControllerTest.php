<?php

namespace Tests\Feature\Dashboard;

use App\Models\Author;
use App\Models\Book;
use App\Models\BookInstance;
use App\Models\Borrowing;
use App\Models\Category;
use App\Models\InstanceState;
use App\Models\Publisher;
use App\Models\User;
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
            'cover_url'          => null,
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

        for ($i = 0; $i < 5; $i++) {
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
        }

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

    public function test_index_returns_borrowings_list(): void
    {
        $token = $this->librarian->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/borrowings')
            ->assertStatus(200)
            ->assertJsonStructure(['data', 'meta']);
    }
}
