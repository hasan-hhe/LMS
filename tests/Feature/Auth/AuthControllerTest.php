<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    private function validRegisterPayload(array $overrides = []): array
    {
        return array_merge([
            'first_name'              => 'أحمد',
            'last_name'               => 'محمد',
            'email'                   => 'ahmed@example.com',
            'phone'                   => '0501234567',
            'identity_number'         => '1234567890',
            'password'                => 'password123',
            'password_confirmation'   => 'password123',
        ], $overrides);
    }

    public function test_register_creates_user_and_returns_token(): void
    {
        $response = $this->postJson('/api/v1/auth/register', $this->validRegisterPayload());

        $response->assertStatus(201)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => ['user', 'token'],
            ]);

        $this->assertDatabaseHas('users', ['email' => 'ahmed@example.com']);
    }

    public function test_register_fails_with_missing_required_fields(): void
    {
        $response = $this->postJson('/api/v1/auth/register', []);

        $response->assertStatus(422)
            ->assertJsonFragment(['status' => 'error']);
    }

    public function test_register_fails_with_duplicate_email(): void
    {
        User::factory()->create(['email' => 'ahmed@example.com']);

        $response = $this->postJson('/api/v1/auth/register', $this->validRegisterPayload());

        $response->assertStatus(422);
    }

    public function test_register_stores_profile_photo(): void
    {
        Storage::fake('public');

        $payload = $this->validRegisterPayload();
        $payload['photo_image'] = UploadedFile::fake()->image('photo.jpg');

        $response = $this->postJson('/api/v1/auth/register', $payload);

        $response->assertStatus(201);
        $user = User::where('email', 'ahmed@example.com')->first();
        $this->assertNotNull($user->photo_url);
        Storage::disk('public')->assertExists($user->photo_url);
    }

    public function test_login_returns_token_for_valid_credentials(): void
    {
        User::factory()->create([
            'email'         => 'test@example.com',
            'password_hash' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email'    => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => ['user', 'token'],
            ]);
    }

    public function test_login_fails_with_wrong_password(): void
    {
        User::factory()->create([
            'email'         => 'test@example.com',
            'password_hash' => bcrypt('correctpassword'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email'    => 'test@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401);
    }

    public function test_logout_deletes_token(): void
    {
        $user = User::factory()->create(['password_hash' => bcrypt('password')]);
        $token = $user->createToken('auth_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/auth/logout');

        $response->assertStatus(200)
            ->assertJsonFragment(['message' => 'تم تسجيل الخروج بنجاح']);
    }

    public function test_me_returns_authenticated_user(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('auth_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/auth/me');

        $response->assertStatus(200)
            ->assertJsonPath('data.email', $user->email);
    }
}
