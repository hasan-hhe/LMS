<?php

namespace App\Services;

use App\Models\TopUpCode;
use App\Models\User;
use App\Notifications\PointsTopUpNotification;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TopUpCodeService
{
    public function __construct(private PointService $pointService, private FineService $fineService) {}

    public function generateBatch(int $count, int $pointsValue, ?string $expiresAt, ?int $userId, int $createdBy): array
    {
        if ($userId && ! User::where('id', $userId)->where('role', 'MEMBER')->exists()) {
            throw new \Exception('العضو المحدد غير موجود');
        }

        return DB::transaction(function () use ($count, $pointsValue, $expiresAt, $userId, $createdBy) {
            $codes = [];
            for ($i = 0; $i < $count; $i++) {
                do {
                    $code = 'LMS-PTS-'.Str::upper(Str::random(8));
                } while (TopUpCode::where('code', $code)->exists());

                $codes[] = TopUpCode::create([
                    'code' => $code, 'points_value' => $pointsValue, 'expires_at' => $expiresAt,
                    'user_id' => $userId, 'created_by' => $createdBy,
                ]);
            }

            return $codes;
        });
    }

    public function redeem(string $code, int $userId): TopUpCode
    {
        $topUp = DB::transaction(function () use ($code, $userId) {
            $topUp = TopUpCode::where('code', Str::upper(trim($code)))->lockForUpdate()->first();
            if (! $topUp) {
                throw new \Exception('رمز شحن النقاط غير موجود');
            }
            if ($topUp->is_used) {
                throw new \Exception('تم استخدام رمز الشحن مسبقاً');
            }
            if ($topUp->expires_at?->isPast()) {
                throw new \Exception('انتهت صلاحية رمز الشحن');
            }
            if ($topUp->user_id && $topUp->user_id !== $userId) {
                throw new \Exception('رمز الشحن مخصص لعضو آخر');
            }

            $topUp->update(['is_used' => true, 'used_at' => now(), 'used_by' => $userId]);
            $this->pointService->credit($userId, $topUp->points_value, 'top_up', TopUpCode::class, (string) $topUp->id, 'شحن رصيد النقاط');

            return $topUp->fresh(['boundUser', 'usedBy', 'creator']);
        });

        try {
            User::find($userId)?->notify(new PointsTopUpNotification($topUp));
        } catch (\Throwable $notificationError) {
            report($notificationError);
        }

        try {
            $this->fineService->settleUnpaidFinesFromBalance($userId);
        } catch (\Throwable $settleError) {
            report($settleError);
        }

        return $topUp;
    }

    public function list(array $filters): LengthAwarePaginator
    {
        $query = TopUpCode::with(['boundUser', 'usedBy', 'creator'])->latest('id');
        if (isset($filters['is_used'])) {
            $query->where('is_used', filter_var($filters['is_used'], FILTER_VALIDATE_BOOLEAN));
        }
        if (! empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        return $query->paginate((int) ($filters['per_page'] ?? 15));
    }

    public function deleteUnused(int $id): void
    {
        $code = TopUpCode::findOrFail($id);
        if ($code->is_used) {
            throw new \Exception('لا يمكن حذف رمز شحن مستخدم');
        }
        $code->delete();
    }
}
