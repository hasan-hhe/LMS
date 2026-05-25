<?php

namespace Tests\Feature\Dashboard;

use App\Models\Author;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'ADMIN', 'password_hash' => bcrypt('password')]);
    }

    public function test_index_returns_authors_list(): void
    {
        Author::create(['firstname' => 'محمد', 'lastname' => 'الغزالي', 'nationality' => 'مصري']);
        $token = $this->admin->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/authors');

        $response->assertStatus(200)->assertJsonStructure(['data', 'meta']);
    }

    public function test_store_creates_author(): void
    {
        $token = $this->admin->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/authors', [
                'firstname'   => 'نجيب',
                'lastname'    => 'محفوظ',
                'nationality' => 'مصري',
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('authers', ['firstname' => 'نجيب']);
    }

    public function test_store_fails_without_required_fields(): void
    {
        $token = $this->admin->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/authors', [])
            ->assertStatus(422);
    }

    public function test_show_returns_author(): void
    {
        $author = Author::create(['firstname' => 'نجيب', 'lastname' => 'محفوظ', 'nationality' => 'مصري']);
        $token  = $this->admin->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson("/api/v1/authors/{$author->id}")
            ->assertStatus(200)
            ->assertJsonPath('data.lastname', 'محفوظ');
    }

    public function test_update_modifies_author(): void
    {
        $author = Author::create(['firstname' => 'نجيب', 'lastname' => 'محفوظ', 'nationality' => 'مصري']);
        $token  = $this->admin->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson("/api/v1/authors/{$author->id}", [
                'firstname'   => 'طه',
                'lastname'    => 'حسين',
                'nationality' => 'مصري',
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.firstname', 'طه');
    }

    public function test_destroy_deletes_author(): void
    {
        $author = Author::create(['firstname' => 'نجيب', 'lastname' => 'محفوظ', 'nationality' => 'مصري']);
        $token  = $this->admin->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->deleteJson("/api/v1/authors/{$author->id}")
            ->assertStatus(200);

        $this->assertDatabaseMissing('authers', ['id' => $author->id]);
    }
}
