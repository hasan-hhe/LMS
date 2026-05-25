<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'first_name'      => fake()->firstName(),
            'last_name'       => fake()->lastName(),
            'email'           => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'phone'           => fake()->unique()->numerify('05########'),
            'identity_number' => fake()->unique()->numerify('##########'),
            'role'            => 'MEMBER',
            'adress'          => fake()->address(),
            'photo_url'       => null,
            'password_hash'   => static::$password ??= Hash::make('password'),
            'remember_token'  => Str::random(10),
        ];
    }

    public function admin(): static
    {
        return $this->state(fn(array $attributes) => ['role' => 'ADMIN']);
    }

    public function librarian(): static
    {
        return $this->state(fn(array $attributes) => ['role' => 'LIBRARIAN']);
    }

    public function unverified(): static
    {
        return $this->state(fn(array $attributes) => ['email_verified_at' => null]);
    }
}
