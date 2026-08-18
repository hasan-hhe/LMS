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
        if (! in_array($key, ['syp_per_point', 'reward_return_on_time'], true)) {
            throw new \Exception('إعداد النقاط غير مدعوم');
        }
        if (! is_numeric($value) || (float) $value < 0 || ($key === 'syp_per_point' && (float) $value <= 0)) {
            throw new \Exception('قيمة إعداد النقاط غير صالحة');
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
}
