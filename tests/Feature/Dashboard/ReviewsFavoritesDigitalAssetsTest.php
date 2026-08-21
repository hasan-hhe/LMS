<?php

namespace Tests\Feature\Dashboard;

use App\Models\Author;
use App\Models\Book;
use App\Models\BookInstance;
use App\Models\Borrowing;
use App\Models\Category;
use App\Models\DigitalAsset;
use App\Models\InstanceState;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderState;
use App\Models\Publisher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
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

    public function test_member_cannot_review_without_return_or_purchase(): void
    {
        $this->actingAs($this->member)
            ->postJson('/api/v1/member/reviews', [
                'isbn' => $this->book->ISBN,
                'rate' => 4,
                'comment' => 'كتاب ممتاز',
            ])
            ->assertStatus(422)
            ->assertJsonPath('body', 'يمكن التقييم فقط بعد إعادة الكتاب من الاستعارة أو بعد شراء الكتاب');
    }

    public function test_member_can_review_once_after_returning_a_borrowing(): void
    {
        $this->markBookReturnedForMember();

        $this->actingAs($this->member)
            ->postJson('/api/v1/member/reviews', [
                'isbn' => $this->book->ISBN,
                'rate' => 4,
                'comment' => 'كتاب ممتاز',
            ])
            ->assertOk()
            ->assertJsonPath('data.rate', 4);

        $this->actingAs($this->member)
            ->postJson('/api/v1/member/reviews', [
                'isbn' => $this->book->ISBN,
                'rate' => 2,
                'comment' => 'محاولة ثانية',
            ])
            ->assertStatus(422)
            ->assertJsonPath('body', 'لقد قيّمت هذا الكتاب مسبقاً');

        $this->assertDatabaseCount('reviews', 1);
        $this->assertSame(4.0, $this->book->fresh()->rate_avg);
    }

    public function test_member_can_review_after_purchasing_the_book(): void
    {
        $this->markBookPurchasedForMember();

        $this->actingAs($this->member)
            ->postJson('/api/v1/member/reviews', [
                'isbn' => $this->book->ISBN,
                'rate' => 5,
                'comment' => 'بعد الشراء',
            ])
            ->assertOk()
            ->assertJsonPath('data.rate', 5);

        $this->assertDatabaseHas('reviews', [
            'user_id' => $this->member->id,
            'book_ISBN' => $this->book->ISBN,
            'rate' => 5,
        ]);
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
        Storage::fake('local');
        $pdf = UploadedFile::fake()->create('book.pdf', 200, 'application/pdf');
        $audio = UploadedFile::fake()->create('book.mp3', 300, 'audio/mpeg');

        $this->actingAs($this->admin)
            ->post('/api/v1/books/'.$this->book->ISBN.'/digital', [
                'pdf' => $pdf,
                'is_free' => 0,
            ], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('data.has_pdf', true)
            ->assertJsonPath('data.has_audio', false)
            ->assertJsonPath('data.locked', false);

        $asset = DigitalAsset::query()->where('book_ISBN', $this->book->ISBN)->first();
        $this->assertNotNull($asset);
        Storage::disk('local')->assertExists($asset->pdf_url);
        $this->assertStringContainsString('/api/v1/books/'.$this->book->ISBN.'/digital/pdf', (string) $this->actingAs($this->admin)->getJson('/api/v1/books/'.$this->book->ISBN.'/digital')->json('data.pdf_url'));

        $this->actingAs($this->admin)
            ->post('/api/v1/books/'.$this->book->ISBN.'/digital', [
                'audio' => $audio,
                'is_free' => 1,
            ], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('data.is_free', true)
            ->assertJsonPath('data.has_audio', true);

        $asset->refresh();
        Storage::disk('local')->assertExists($asset->pdf_url);
        Storage::disk('local')->assertExists($asset->audio_url);
        $this->assertTrue($asset->is_free);
    }

    public function test_member_only_receives_urls_for_accessible_digital_asset(): void
    {
        Storage::fake('local');
        $path = UploadedFile::fake()->create('locked.pdf', 120, 'application/pdf')->store('digital/pdfs', 'local');
        $asset = $this->book->digitalAsset()->create([
            'pdf_url' => $path,
            'is_free' => false,
        ]);

        $this->actingAs($this->member)
            ->getJson('/api/books/'.$this->book->ISBN)
            ->assertOk()
            ->assertJsonPath('book.digital.locked', true)
            ->assertJsonPath('book.digital.has_pdf', true)
            ->assertJsonPath('book.digital.pdf_url', null);

        $asset->update(['is_free' => true]);

        $response = $this->actingAs($this->member)
            ->getJson('/api/books/'.$this->book->ISBN)
            ->assertOk()
            ->assertJsonPath('book.digital.locked', false)
            ->assertJsonPath('book.digital.has_pdf', true);

        $pdfUrl = $response->json('book.digital.pdf_url');
        $this->assertIsString($pdfUrl);
        $this->assertStringContainsString('/api/v1/books/'.$this->book->ISBN.'/digital/pdf', $pdfUrl);
    }

    public function test_staff_can_show_and_delete_digital_asset(): void
    {
        Storage::fake('local');
        $pdf = UploadedFile::fake()->create('book.pdf', 200, 'application/pdf');

        $this->actingAs($this->admin)
            ->post('/api/v1/books/'.$this->book->ISBN.'/digital', [
                'pdf' => $pdf,
                'is_free' => 0,
            ], ['Accept' => 'application/json'])
            ->assertOk();

        $path = DigitalAsset::query()->where('book_ISBN', $this->book->ISBN)->value('pdf_url');
        Storage::disk('local')->assertExists($path);

        $this->actingAs($this->admin)
            ->getJson('/api/v1/books/'.$this->book->ISBN.'/digital')
            ->assertOk()
            ->assertJsonPath('data.has_pdf', true);

        $this->actingAs($this->admin)
            ->deleteJson('/api/v1/books/'.$this->book->ISBN.'/digital')
            ->assertOk();

        $this->assertDatabaseMissing('digital_assets', [
            'book_ISBN' => $this->book->ISBN,
        ]);
        Storage::disk('local')->assertMissing($path);
    }

    public function test_signed_digital_file_can_be_downloaded(): void
    {
        Storage::fake('local');
        $path = UploadedFile::fake()->create('book.pdf', 120, 'application/pdf')->store('digital/pdfs', 'local');
        $this->book->digitalAsset()->create([
            'pdf_url' => $path,
            'is_free' => true,
        ]);

        $this->get('/api/v1/books/'.$this->book->ISBN.'/digital/pdf')
            ->assertForbidden();

        $url = URL::temporarySignedRoute('digital.file', now()->addHour(), [
            'isbn' => $this->book->ISBN,
            'type' => 'pdf',
        ]);

        $this->get($url)
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_creating_digital_asset_without_file_is_rejected(): void
    {
        $this->actingAs($this->admin)
            ->postJson('/api/v1/books/'.$this->book->ISBN.'/digital', [
                'is_free' => true,
            ])
            ->assertStatus(422);
    }

    public function test_staff_can_list_and_delete_reviews(): void
    {
        $this->markBookReturnedForMember();

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

    private function markBookReturnedForMember(): void
    {
        $available = InstanceState::firstOrCreate(['state' => 'available']);
        $instance = BookInstance::create([
            'book_ISBN' => $this->book->ISBN,
            'state_id' => $available->id,
            'condition' => 'new',
        ]);

        Borrowing::create([
            'member_id' => $this->member->id,
            'librarian_id' => $this->admin->id,
            'book_instance_id' => $instance->id,
            'start_date' => now()->subDays(10)->toDateString(),
            'end_date' => now()->subDays(3)->toDateString(),
            'due_date' => now()->subDays(3)->toDateString(),
            'returned_at' => now()->subDay(),
            'borrowing_cast' => 0,
            'is_paid' => false,
        ]);
    }

    private function markBookPurchasedForMember(): void
    {
        $confirmed = OrderState::firstOrCreate(['state' => 'confirmed']);
        $order = Order::create([
            'user_id' => $this->member->id,
            'state_id' => $confirmed->id,
            'total_prices' => 100,
            'total_points' => 10,
            'total_amount' => 1,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'book_ISBN' => $this->book->ISBN,
            'price_once' => 100,
            'count' => 1,
        ]);
    }
}
