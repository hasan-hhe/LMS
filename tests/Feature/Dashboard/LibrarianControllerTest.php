<?php

namespace Tests\Feature\Dashboard;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LibrarianControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $librarian;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin     = User::factory()->create(['role' => 'ADMIN', 'password_hash' => bcrypt('password')]);
        $this->librarian = User::factory()->create(['role' => 'LIBRARIAN', 'password_hash' => bcrypt('password')]);
    }

    private function validLibrarianPayload(array $overrides = []): array
    {
        return array_merge([
            'first_name'            => 'محمد',
            'last_name'             => 'علي',
            'email'                 => 'librarian2@example.com',
            'phone'                 => '0559876543',
            'identity_number'       => '1234567890',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ], $overrides);
    }

    public function test_index_returns_librarians_for_admin(): void
    {
        User::factory()->create(['role' => 'LIBRARIAN', 'password_hash' => bcrypt('p')]);
        $token = $this->admin->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/librarians')
            ->assertStatus(200)
            ->assertJsonStructure(['data', 'meta']);
    }

    public function test_index_forbidden_for_librarian(): void
    {
        $token = $this->librarian->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/librarians')
            ->assertStatus(403);
    }

    public function test_store_creates_librarian(): void
    {
        $token = $this->admin->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/librarians', $this->validLibrarianPayload())
            ->assertStatus(201);

        $this->assertDatabaseHas('users', ['email' => 'librarian2@example.com', 'role' => 'LIBRARIAN']);
    }

    public function test_show_returns_librarian_data(): void
    {
        $token = $this->admin->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson("/api/v1/librarians/{$this->librarian->id}")
            ->assertStatus(200)
            ->assertJsonPath('data.email', $this->librarian->email);
    }

    public function test_show_returns_404_for_non_librarian(): void
    {
        $member = User::factory()->create(['role' => 'MEMBER', 'password_hash' => bcrypt('p')]);
        $token  = $this->admin->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson("/api/v1/librarians/{$member->id}")
            ->assertStatus(404);
    }

    public function test_update_modifies_librarian(): void
    {
        $token = $this->admin->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson("/api/v1/librarians/{$this->librarian->id}", ['first_name' => 'سارة'])
            ->assertStatus(200)
            ->assertJsonPath('data.first_name', 'سارة');
    }

    public function test_destroy_deletes_librarian(): void
    {
        $librarian = User::factory()->create(['role' => 'LIBRARIAN', 'password_hash' => bcrypt('p')]);
        $token     = $this->admin->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->deleteJson("/api/v1/librarians/{$librarian->id}")
            ->assertStatus(200);

        $this->assertDatabaseMissing('users', ['id' => $librarian->id]);
    }
}
