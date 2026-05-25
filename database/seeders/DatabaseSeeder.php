<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            LookupStatesSeeder::class,
            DemoUsersSeeder::class,
            DemoCatalogSeeder::class,
            DemoCirculationSeeder::class,
            DemoOrdersSeeder::class,
        ]);

        $this->command->info('');
        $this->command->info('--- LMS Demo Credentials ---');
        $this->command->info('Admin:     admin@lms.test / password');
        $this->command->info('Librarian: librarian@lms.test / password');
        $this->command->info('Members:   member1@lms.test / password (and member2, member3)');
        $this->command->info('Dashboard: /admin/login');
        $this->command->info('');
    }
}
