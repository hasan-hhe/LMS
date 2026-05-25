<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class AuthService
{
    public function register(array $data, $photoFile = null): array
    {
        DB::beginTransaction();
        try {
            $photoUrl = $this->storeProfilePhoto($photoFile);

            $user = User::create([
                'first_name'      => $data['first_name'],
                'last_name'       => $data['last_name'],
                'email'           => $data['email'],
                'phone'           => $data['phone'],
                'identity_number' => $data['identity_number'],
                'adress'          => $data['adress'] ?? null,
                'role'            => 'MEMBER',
                'photo_url'       => $photoUrl,
                'password_hash'   => Hash::make($data['password']),
                'participe_end_date' => $data['participe_end_date'] ?? null,
            ]);

            $token = $user->createToken('auth_token')->plainTextToken;

            DB::commit();

            return ['user' => $user, 'token' => $token];
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function login(string $email, string $password): array
    {
        try {
            $user = User::where('email', $email)->first();

            if (!$user || !Hash::check($password, $user->password_hash)) {
                throw new \Exception('البريد الإلكتروني أو كلمة المرور غير صحيحة');
            }

            $user->tokens()->delete();
            $token = $user->createToken('auth_token')->plainTextToken;

            return ['user' => $user, 'token' => $token];
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function logout(User $user): void
    {
        try {
            $user->currentAccessToken()->delete();
        } catch (\Exception $e) {
            throw $e;
        }
    }

    private function storeProfilePhoto($photoFile): ?string
    {
        if (!$photoFile) {
            return null;
        }

        return Storage::disk('public')->putFile('profiles', $photoFile);
    }
}
