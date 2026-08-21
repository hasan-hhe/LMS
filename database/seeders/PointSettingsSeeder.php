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
        PointSetting::updateOrCreate(['key' => 'fine_per_day_syp'], ['value' => '0.5']);
        PointSetting::updateOrCreate(['key' => 'fine_per_day_points'], ['value' => '1']);
        PointSetting::updateOrCreate(['key' => 'loan_period_days'], ['value' => '14']);
        PointSetting::updateOrCreate(['key' => 'membership_points'], ['value' => '20']);
        PointSetting::updateOrCreate(['key' => 'membership_days'], ['value' => '365']);
    }
}
