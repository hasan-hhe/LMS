<?php

namespace Database\Seeders;

use App\Models\InstanceState;
use App\Models\OrderState;
use App\Models\ReservationState;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LookupStatesSeeder extends Seeder
{
    public function run(): void
    {
        DB::beginTransaction();
        try {
            foreach (['available', 'borrowed', 'reserved', 'damaged', 'lost'] as $state) {
                InstanceState::firstOrCreate(['state' => $state]);
            }

            foreach (['pending', 'confirmed', 'cancelled', 'rejected'] as $state) {
                OrderState::firstOrCreate(['state' => $state]);
            }

            foreach (['pending', 'cancelled'] as $state) {
                ReservationState::firstOrCreate(['state' => $state]);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
