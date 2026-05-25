<?php

namespace Tests\Feature\Dashboard;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $librarian;

    protected function setUp(): void
    {
        parent::setUp();
        $this->librarian = User::factory()->create(['role' => 'LIBRARIAN', 'password_hash' => bcrypt('password')]);
    }

    private function validMemberPayload(array $overrides = []): array
    {
        return array_merge([
            'first_name'            => 'سارة',
            'last_name'             => 'أحمد',
            'email'                 => 'sara@example.com',
            'phone'                 => '0551234567',
            'identity_number'       => '9876543210',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ], $overrides);
    }

    public function test_index_returns_members(): void
    {
        User::factory()->create(['role' => 'MEMBER', 'password_hash' => bcrypt('p')]);
        $token = $this->librarian->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/members')
            ->assertStatus(200)
            ->assertJsonStructure(['data', 'meta']);
    }

    public function test_store_creates_member(): void
    {
        $token = $this->librarian->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/members', $this->validMemberPayload());

        $response->assertStatus(201);
        $this->assertDatabaseHas('users', ['email' => 'sara@example.com', 'role' => 'MEMBER']);
    }

    public function test_show_returns_member_data(): void
    {
        $member = User::factory()->create(['role' => 'MEMBER', 'password_hash' => bcrypt('p')]);
        $token  = $this->librarian->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson("/api/v1/members/{$member->id}")
            ->assertStatus(200)
            ->assertJsonPath('data.email', $member->email);
    }

    public function test_show_returns_404_for_non_member(): void
    {
        $token = $this->librarian->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/members/99999')
            ->assertStatus(404);
    }

    public function test_update_modifies_member(): void
    {
        $member = User::factory()->create(['role' => 'MEMBER', 'password_hash' => bcrypt('p')]);
        $token  = $this->librarian->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson("/api/v1/members/{$member->id}", ['first_name' => 'فاطمة'])
            ->assertStatus(200)
            ->assertJsonPath('data.first_name', 'فاطمة');
    }

    public function test_destroy_deletes_member(): void
    {
        $member = User::factory()->create(['role' => 'MEMBER', 'password_hash' => bcrypt('p')]);
        $token  = $this->librarian->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->deleteJson("/api/v1/members/{$member->id}")
            ->assertStatus(200);

        $this->assertDatabaseMissing('users', ['id' => $member->id]);
    }
}
