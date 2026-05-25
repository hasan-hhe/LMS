<?php

namespace Tests\Feature\Dashboard;

use App\Models\Author;
use App\Models\Book;
use App\Models\BookInstance;
use App\Models\Borrowing;
use App\Models\InstanceState;
use App\Models\LateFine;
use App\Models\Category;
use App\Models\Publisher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FineControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $librarian;
    private LateFine $fine;

    protected function setUp(): void
    {
        parent::setUp();

        $this->librarian = User::factory()->create(['role' => 'LIBRARIAN', 'password_hash' => bcrypt('p')]);
        $member          = User::factory()->create(['role' => 'MEMBER', 'password_hash' => bcrypt('p')]);

        $state    = InstanceState::create(['state' => 'available']);
        $author   = Author::create(['firstname' => 'م', 'lastname' => 'ن', 'nationality' => 'أ']);
        $category = Category::create(['title' => 'عام', 'discription' => 'وصف']);
        $pub      = Publisher::create(['name' => 'نشر', 'location' => 'مكان']);
        $book     = Book::create([
            'ISBN' => '978-fine-test', 'auther_id' => $author->id, 'catagory_id' => $category->id,
            'publisher_id' => $pub->id, 'title' => 'ك', 'discription' => 'و', 'price' => 20,
            'amount' => 1, 'rate_avg' => 0, 'cover_url' => null, 'year_of_publishing' => '2020', 'number_edition' => '1',
        ]);
        $instance = BookInstance::create(['book_ISBN' => $book->ISBN, 'state_id' => $state->id, 'condition' => 'new']);

        $borrowing = Borrowing::create([
            'member_id' => $member->id, 'librarian_id' => $this->librarian->id,
            'book_instance_id' => $instance->id,
            'start_date' => now()->subDays(20), 'end_date' => now()->subDays(5),
            'due_date' => now()->subDays(5), 'borrowing_cast' => 0, 'is_paid' => false,
        ]);

        $this->fine = LateFine::create([
            'borrowing_id' => $borrowing->id,
            'days_late'    => 5,
            'fine'         => 2.5,
            'is_paid'      => false,
        ]);
    }

    public function test_index_returns_fines_list(): void
    {
        $token = $this->librarian->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/fines')
            ->assertStatus(200)
            ->assertJsonStructure(['data', 'meta']);
    }

    public function test_pay_marks_fine_as_paid(): void
    {
        $token = $this->librarian->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson("/api/v1/fines/{$this->fine->id}/pay");

        $response->assertStatus(200)
            ->assertJsonPath('data.is_paid', true);

        $this->assertDatabaseHas('late_fines', ['id' => $this->fine->id, 'is_paid' => true]);
    }

    public function test_pay_fails_if_already_paid(): void
    {
        $this->fine->update(['is_paid' => true, 'paid_at' => now()]);
        $token = $this->librarian->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson("/api/v1/fines/{$this->fine->id}/pay")
            ->assertStatus(422);
    }
}
