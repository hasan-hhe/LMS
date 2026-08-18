<?php

namespace Tests\Feature\Dashboard;

use App\Models\Author;
use App\Models\Book;
use App\Models\Category;
use App\Models\Publisher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewsFavoritesDigitalAssetsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $member;

    private Book $book;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'ADMIN']);
        $this->member = User::factory()->create(['role' => 'MEMBER']);
        $author = Author::create(['firstname' => 'نجيب', 'lastname' => 'محفوظ', 'nationality' => 'مصري']);
        $category = Category::create(['title' => 'روايات', 'discription' => 'روايات عربية']);
        $publisher = Publisher::create(['name' => 'دار النشر', 'location' => 'دمشق']);
        $this->book = Book::create([
            'ISBN' => '9780000000001',
            'auther_id' => $author->id,
            'catagory_id' => $category->id,
            'publisher_id' => $publisher->id,
            'title' => 'كتاب رقمي',
            'discription' => 'وصف الكتاب',
            'price' => 100,
            'price_points' => 10,
            'amount' => 1,
            'rate_avg' => 0,
            'cover_url' => '',
            'year_of_publishing' => '2026',
            'number_edition' => '1',
        ]);
    }

    public function test_member_can_create_review_and_book_average_is_recalculated(): void
    {
        $response = $this->actingAs($this->member)
            ->postJson('/api/v1/member/reviews', [
                'isbn' => $this->book->ISBN,
                'rate' => 4,
                'comment' => 'كتاب ممتاز',
            ]);

        $response->assertOk()->assertJsonPath('data.rate', 4);

        $this->actingAs($this->member)
            ->postJson('/api/v1/member/reviews', [
                'isbn' => $this->book->ISBN,
                'rate' => 2,
                'comment' => 'تم تحديث التقييم',
            ])
            ->assertOk()
            ->assertJsonPath('data.rate', 2);

        $this->assertDatabaseHas('reviews', [
            'user_id' => $this->member->id,
            'book_ISBN' => $this->book->ISBN,
            'rate' => 2,
        ]);
        $this->assertDatabaseCount('reviews', 1);
        $this->assertSame(2.0, $this->book->fresh()->rate_avg);
    }

    public function test_member_can_add_and_remove_favorite(): void
    {
        $this->actingAs($this->member)
            ->postJson('/api/v1/member/favorites', ['isbn' => $this->book->ISBN])
            ->assertCreated()
            ->assertJsonPath('data.book.isbn', $this->book->ISBN);

        $this->assertDatabaseHas('favorites', [
            'user_id' => $this->member->id,
            'book_ISBN' => $this->book->ISBN,
        ]);

        $this->actingAs($this->member)
            ->deleteJson('/api/v1/member/favorites/'.$this->book->ISBN)
            ->assertOk();

        $this->assertDatabaseMissing('favorites', [
            'user_id' => $this->member->id,
            'book_ISBN' => $this->book->ISBN,
        ]);
    }

    public function test_staff_can_upsert_digital_asset(): void
    {
        $this->actingAs($this->admin)
            ->postJson('/api/v1/books/'.$this->book->ISBN.'/digital', [
                'pdf_url' => 'https://example.com/book.pdf',
                'is_free' => false,
            ])
            ->assertOk()
            ->assertJsonPath('data.pdf_url', 'https://example.com/book.pdf');

        $this->actingAs($this->admin)
            ->putJson('/api/v1/books/'.$this->book->ISBN.'/digital', [
                'audio_url' => 'https://example.com/book.mp3',
                'is_free' => true,
            ])
            ->assertOk()
            ->assertJsonPath('data.is_free', true);

        $this->assertDatabaseHas('digital_assets', [
            'book_ISBN' => $this->book->ISBN,
            'pdf_url' => 'https://example.com/book.pdf',
            'audio_url' => 'https://example.com/book.mp3',
            'is_free' => true,
        ]);
    }

    public function test_member_only_receives_urls_for_accessible_digital_asset(): void
    {
        $asset = $this->book->digitalAsset()->create([
            'pdf_url' => 'https://example.com/locked.pdf',
            'is_free' => false,
        ]);

        $this->actingAs($this->member)
            ->getJson('/api/books/'.$this->book->ISBN)
            ->assertOk()
            ->assertJsonPath('book.digital.locked', true)
            ->assertJsonPath('book.digital.pdf_url', null);

        $asset->update(['is_free' => true]);

        $this->actingAs($this->member)
            ->getJson('/api/books/'.$this->book->ISBN)
            ->assertOk()
            ->assertJsonPath('book.digital.locked', false)
            ->assertJsonPath('book.digital.pdf_url', 'https://example.com/locked.pdf');
    }

    public function test_staff_can_show_and_delete_digital_asset(): void
    {
        $this->actingAs($this->admin)
            ->postJson('/api/v1/books/'.$this->book->ISBN.'/digital', [
                'pdf_url' => 'https://example.com/book.pdf',
                'is_free' => false,
            ])
            ->assertOk();

        $this->actingAs($this->admin)
            ->getJson('/api/v1/books/'.$this->book->ISBN.'/digital')
            ->assertOk()
            ->assertJsonPath('data.pdf_url', 'https://example.com/book.pdf');

        $this->actingAs($this->admin)
            ->deleteJson('/api/v1/books/'.$this->book->ISBN.'/digital')
            ->assertOk();

        $this->assertDatabaseMissing('digital_assets', [
            'book_ISBN' => $this->book->ISBN,
        ]);
    }

    public function test_staff_can_list_and_delete_reviews(): void
    {
        $this->actingAs($this->member)
            ->postJson('/api/v1/member/reviews', [
                'isbn' => $this->book->ISBN,
                'rate' => 5,
                'comment' => 'رائع',
            ])
            ->assertOk();

        $list = $this->actingAs($this->admin)->getJson('/api/v1/reviews');
        $list->assertOk()->assertJsonPath('meta.total', 1);
        $reviewId = $list->json('data.0.id');

        $this->actingAs($this->admin)
            ->deleteJson('/api/v1/reviews/'.$reviewId)
            ->assertOk();

        $this->assertDatabaseMissing('reviews', ['id' => $reviewId]);
    }
}
