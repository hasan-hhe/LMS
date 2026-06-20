<?php

namespace Tests\Feature\Dashboard;

use App\Models\Author;
use App\Models\Book;
use App\Models\Category;
use App\Models\Publisher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BookControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Author $author;
    private Category $category;
    private Publisher $publisher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin     = User::factory()->create(['role' => 'ADMIN', 'password_hash' => bcrypt('password')]);
        $this->author    = Author::create(['firstname' => 'محمد', 'lastname' => 'الغزالي', 'nationality' => 'مصري']);
        $this->category  = Category::create(['title' => 'فلسفة', 'discription' => 'كتب فلسفية']);
        $this->publisher = Publisher::create(['name' => 'دار النشر', 'location' => 'القاهرة']);
    }

    private function validBookPayload(array $overrides = []): array
    {
        return array_merge([
            'ISBN'               => '978-3-16-148410-0',
            'auther_id'          => $this->author->id,
            'catagory_id'        => $this->category->id,
            'publisher_id'       => $this->publisher->id,
            'title'              => 'إحياء علوم الدين',
            'discription'        => 'كتاب إسلامي في الأخلاق',
            'price'              => 29.99,
            'amount'             => 10,
            'year_of_publishing' => '2020',
            'number_edition'     => '1',
        ], $overrides);
    }

    public function test_index_returns_paginated_books(): void
    {
        $token = $this->admin->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/books');

        $response->assertStatus(200)
            ->assertJsonStructure(['message', 'body', 'data', 'meta']);
    }

    public function test_store_creates_book_successfully(): void
    {
        $token = $this->admin->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/books', $this->validBookPayload());

        $response->assertStatus(201)
            ->assertJsonPath('data.title', 'إحياء علوم الدين');

        $this->assertDatabaseHas('books', ['ISBN' => '978-3-16-148410-0']);
    }

    public function test_store_fails_with_duplicate_isbn(): void
    {
        $token = $this->admin->createToken('test')->plainTextToken;
        $this->postJson('/api/v1/books', $this->validBookPayload())
            ->assertHeader('Authorization');

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/books', $this->validBookPayload())
            ->assertStatus(422);
    }

    public function test_store_uploads_cover_image(): void
    {
        Storage::fake('public');
        $token = $this->admin->createToken('test')->plainTextToken;

        $payload                = $this->validBookPayload();
        $payload['cover_image'] = UploadedFile::fake()->image('cover.jpg');

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/books', $payload);

        $response->assertStatus(201);
        $book = Book::find('978-3-16-148410-0');
        Storage::disk('public')->assertExists($book->cover_url);
    }

    public function test_show_returns_book_data(): void
    {
        $token = $this->admin->createToken('test')->plainTextToken;
        Book::create($this->validBookPayload() + ['cover_url' => null, 'rate_avg' => 0]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/books/978-3-16-148410-0');

        $response->assertStatus(200)
            ->assertJsonPath('data.isbn', '978-3-16-148410-0');
    }

    public function test_show_returns_404_for_missing_book(): void
    {
        $token = $this->admin->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/books/non-existent-isbn')
            ->assertStatus(404);
    }

    public function test_update_modifies_book(): void
    {
        $token = $this->admin->createToken('test')->plainTextToken;
        Book::create($this->validBookPayload() + ['cover_url' => null, 'rate_avg' => 0]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson('/api/v1/books/978-3-16-148410-0', ['title' => 'عنوان محدث']);

        $response->assertStatus(200)
            ->assertJsonPath('data.title', 'عنوان محدث');
    }

    public function test_destroy_deletes_book(): void
    {
        $token = $this->admin->createToken('test')->plainTextToken;
        Book::create($this->validBookPayload() + ['cover_url' => null, 'rate_avg' => 0]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->deleteJson('/api/v1/books/978-3-16-148410-0');

        $response->assertStatus(200);
        $this->assertDatabaseMissing('books', ['ISBN' => '978-3-16-148410-0']);
    }

    public function test_unauthenticated_user_cannot_access_books(): void
    {
        $this->getJson('/api/v1/books')->assertStatus(401);
    }
}
