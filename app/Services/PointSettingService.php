<?php

namespace App\Services;

use App\Models\PointSetting;
use Illuminate\Support\Collection;

class PointSettingService
{
    public function __construct(private PointService $pointService) {}

    public function getAll(): Collection
    {
        $this->pointService->ensureSettings();

        return PointSetting::orderBy('key')->get();
    }

    public function update(string $key, string|int|float $value): PointSetting
    {
        if (! in_array($key, [
            'syp_per_point',
            'reward_return_on_time',
            'fine_per_day_syp',
            'fine_per_day_points',
            'loan_period_days',
            'membership_points',
            'membership_days',
        ], true)) {
            throw new \Exception('الإعداد غير مدعوم');
        }
        if (! is_numeric($value) || (float) $value < 0 || (in_array($key, ['syp_per_point', 'fine_per_day_syp', 'loan_period_days', 'membership_days'], true) && (float) $value <= 0)) {
            throw new \Exception('قيمة الإعداد غير صالحة');
        }

        return PointSetting::updateOrCreate(['key' => $key], ['value' => (string) $value]);
    }

    public function getSypPerPoint(): float
    {
        $this->pointService->ensureSettings();

        return (float) PointSetting::where('key', 'syp_per_point')->value('value');
    }

    public function getRewardReturnOnTime(): int
    {
        $this->pointService->ensureSettings();

        return (int) PointSetting::where('key', 'reward_return_on_time')->value('value');
    }

    public function getFinePerDaySyp(): float
    {
        $this->pointService->ensureSettings();

        return (float) PointSetting::where('key', 'fine_per_day_syp')->value('value');
    }

    public function getFinePerDayPoints(): int
    {
        $this->pointService->ensureSettings();

        return (int) PointSetting::where('key', 'fine_per_day_points')->value('value');
    }

    public function getLoanPeriodDays(): int
    {
        $this->pointService->ensureSettings();

        return max(1, (int) PointSetting::where('key', 'loan_period_days')->value('value'));
    }

    public function getMembershipPoints(): int
    {
        $this->pointService->ensureSettings();

        return max(0, (int) PointSetting::where('key', 'membership_points')->value('value'));
    }

    public function getMembershipDays(): int
    {
        $this->pointService->ensureSettings();

        return max(1, (int) PointSetting::where('key', 'membership_days')->value('value'));
    }
}
