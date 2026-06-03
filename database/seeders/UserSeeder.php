<?php

namespace Database\Seeders;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $baseDate = Carbon::now()->subYears(25);

        $admin = [
            'first_name' => 'مدير',
            'last_name' => 'النظام',
            'identity_number' => '1000000001',
            'adress' => 'دمشق - الإدارة',
            'role' => 'ADMIN',
            'email' => 'admin@example.com',
            'email_verified_at' => now(),
            'phone' => '0999999999',
            'password_hash' => Hash::make('admin123'),
            'photo_url' => null,
            'participe_end_date' => Carbon::now()->addYears(1),
            'remember_token' => Str::random(10),
        ];

        User::firstOrCreate(['email' => $admin['email']], $admin);

        $members = [
            ['أحمد', 'محمد', '0911111111', 'ahmed@example.com', 'MEMBER'],
            ['فاطمة', 'علي', '0922222222', 'fatima@example.com', 'MEMBER'],
            ['خالد', 'حسن', '0933333333', 'khalid@example.com', 'MEMBER'],
            ['سارة', 'يوسف', '0944444444', 'sara@example.com', 'LIBRARIAN'],
            ['محمد', 'أحمد', '0955555555', 'mohammed@example.com', 'MEMBER'],
        ];

        foreach ($members as $member) {
            User::firstOrCreate(
                ['email' => $member[3]],
                [
                    'first_name' => $member[0],
                    'last_name' => $member[1],
                    'identity_number' => (string) (2000000000 + random_int(100000, 999999)),
                    'adress' => 'عينة عنوان ' . $member[0],
                    'role' => $member[4],
                    'email' => $member[3],
                    'email_verified_at' => now(),
                    'phone' => $member[2],
                    'password_hash' => Hash::make('password'),
                    'photo_url' => null,
                    'participe_end_date' => $baseDate->copy()->addYears(1)->format('Y-m-d'),
                    'remember_token' => Str::random(10),
                ]
            );
        }

        $firstNames = ['أحمد', 'محمد', 'علي', 'حسن', 'خالد', 'يوسف', 'محمود', 'عمر', 'طارق', 'سامي', 'فاطمة', 'سارة', 'مريم', 'ليلى', 'نور', 'هدى', 'ريم', 'زينب', 'آمنة', 'خديجة'];
        $lastNames = ['محمد', 'أحمد', 'علي', 'حسن', 'يوسف', 'خالد', 'محمود', 'عمر', 'طارق', 'سامي', 'الزهراء', 'الرضا', 'الكاظم', 'الباقر', 'الصادق', 'النور', 'الهدى', 'الرحمة', 'البركة', 'الخير'];

        for ($i = 1; $i <= 20; $i++) {
            User::firstOrCreate(
                ['email' => 'renter' . $i . '@example.com'],
                [
                    'first_name' => $firstNames[array_rand($firstNames)],
                    'last_name' => $lastNames[array_rand($lastNames)],
                    'identity_number' => (string) (3000000000 + $i),
                    'adress' => 'عنوان تجريبي ' . $i,
                    'role' => 'MEMBER',
                    'email' => 'renter' . $i . '@example.com',
                    'email_verified_at' => now(),
                    'phone' => '09' . str_pad($i, 8, '0', STR_PAD_LEFT),
                    'password_hash' => Hash::make('password'),
                    'photo_url' => null,
                    'participe_end_date' => Carbon::now()->addYears(1)->format('Y-m-d'),
                    'remember_token' => Str::random(10),
                ]
            );
        }
    }
}
