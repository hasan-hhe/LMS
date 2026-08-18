<?php

namespace Database\Seeders;

use App\Models\PointSetting;
use Illuminate\Database\Seeder;

class PointSettingsSeeder extends Seeder
{
    public function run(): void
    {
        PointSetting::updateOrCreate(['key' => 'syp_per_point'], ['value' => '100']);
        PointSetting::updateOrCreate(['key' => 'reward_return_on_time'], ['value' => '5']);
    }
}
