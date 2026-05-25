<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DemoUsersSeeder extends Seeder
{
    public const DEMO_PASSWORD = 'password';

    public function run(): void
    {
        DB::beginTransaction();
        try {
            User::updateOrCreate(
                ['email' => 'admin@lms.test'],
                [
                    'first_name'      => 'مدير',
                    'last_name'       => 'النظام',
                    'phone'           => '0500000001',
                    'identity_number' => '1000000001',
                    'role'            => 'ADMIN',
                    'adress'          => 'المكتبة المركزية',
                    'password_hash'   => Hash::make(self::DEMO_PASSWORD),
                ]
            );

            User::updateOrCreate(
                ['email' => 'librarian@lms.test'],
                [
                    'first_name'      => 'أمين',
                    'last_name'       => 'المكتبة',
                    'phone'           => '0500000002',
                    'identity_number' => '1000000002',
                    'role'            => 'LIBRARIAN',
                    'adress'          => 'المكتبة المركزية',
                    'password_hash'   => Hash::make(self::DEMO_PASSWORD),
                ]
            );

            $members = [
                ['email' => 'member1@lms.test', 'first_name' => 'أحمد', 'last_name' => 'محمد', 'phone' => '0501000001', 'identity_number' => '2000000001'],
                ['email' => 'member2@lms.test', 'first_name' => 'فاطمة', 'last_name' => 'علي', 'phone' => '0501000002', 'identity_number' => '2000000002'],
                ['email' => 'member3@lms.test', 'first_name' => 'خالد', 'last_name' => 'سعيد', 'phone' => '0501000003', 'identity_number' => '2000000003'],
            ];

            foreach ($members as $member) {
                User::updateOrCreate(
                    ['email' => $member['email']],
                    [
                        'first_name'         => $member['first_name'],
                        'last_name'          => $member['last_name'],
                        'phone'              => $member['phone'],
                        'identity_number'    => $member['identity_number'],
                        'role'               => 'MEMBER',
                        'adress'             => 'الرياض',
                        'password_hash'      => Hash::make(self::DEMO_PASSWORD),
                        'participe_end_date' => now()->addYear()->toDateString(),
                    ]
                );
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
