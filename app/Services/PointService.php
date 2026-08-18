<?php

namespace App\Services;

use App\Models\PointSetting;
use App\Models\PointTransaction;
use App\Models\UserPoint;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class PointService
{
    public function getOrCreateBalance(int $userId): UserPoint
    {
        return UserPoint::firstOrCreate(['user_id' => $userId], ['balance' => 0]);
    }

    public function getBalance(int $userId): int
    {
        return $this->getOrCreateBalance($userId)->balance;
    }

    public function getHistory(int $userId, int $paginate = 15): LengthAwarePaginator
    {
        return PointTransaction::where('user_id', $userId)->latest('id')->paginate($paginate);
    }

    public function credit(int $userId, int $points, string $type, ?string $refType = null, ?string $refId = null, ?string $note = null): PointTransaction
    {
        if ($points <= 0) {
            throw new \Exception('يجب أن تكون النقاط المضافة أكبر من صفر');
        }

        return DB::transaction(function () use ($userId, $points, $type, $refType, $refId, $note) {
            $wallet = UserPoint::where('user_id', $userId)->lockForUpdate()->first()
                ?? UserPoint::create(['user_id' => $userId, 'balance' => 0]);
            $wallet->increment('balance', $points);

            return PointTransaction::create([
                'user_id' => $userId, 'points' => $points, 'type' => $type,
                'reference_type' => $refType, 'reference_id' => $refId, 'note' => $note,
            ]);
        });
    }

    public function debit(int $userId, int $points, string $type, ?string $refType = null, ?string $refId = null, ?string $note = null): PointTransaction
    {
        if ($points <= 0) {
            throw new \Exception('يجب أن تكون النقاط المخصومة أكبر من صفر');
        }

        return DB::transaction(function () use ($userId, $points, $type, $refType, $refId, $note) {
            $wallet = UserPoint::where('user_id', $userId)->lockForUpdate()->first()
                ?? UserPoint::create(['user_id' => $userId, 'balance' => 0]);
            if ($wallet->balance < $points) {
                throw new \Exception('رصيد النقاط غير كافٍ لإتمام العملية');
            }
            $wallet->decrement('balance', $points);

            return PointTransaction::create([
                'user_id' => $userId, 'points' => -$points, 'type' => $type,
                'reference_type' => $refType, 'reference_id' => $refId, 'note' => $note,
            ]);
        });
    }

    public function adjust(int $userId, int $delta, string $note, int $adminId): PointTransaction
    {
        if ($delta === 0) {
            throw new \Exception('يجب ألا يكون تعديل النقاط صفراً');
        }
        $fullNote = $note." (بواسطة المستخدم رقم {$adminId})";

        return $delta > 0
            ? $this->credit($userId, $delta, 'adjust', 'admin', (string) $adminId, $fullNote)
            : $this->debit($userId, abs($delta), 'adjust', 'admin', (string) $adminId, $fullNote);
    }

    public function sypToPoints(float $syp): int
    {
        if ($syp <= 0) {
            return 0;
        }
        $this->ensureSettings();
        $rate = max(1, (float) PointSetting::where('key', 'syp_per_point')->value('value'));

        return max(1, (int) ceil($syp / $rate));
    }

    public function ensureSettings(): void
    {
        PointSetting::firstOrCreate(['key' => 'syp_per_point'], ['value' => '100']);
        PointSetting::firstOrCreate(['key' => 'reward_return_on_time'], ['value' => '5']);
    }
}
