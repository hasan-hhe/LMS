<?php

namespace App\Services;

use App\Models\Borrowing;
use App\Models\LateFine;
use App\Notifications\DamageFineNotification;
use App\Notifications\FineAccumulatedNotification;
use App\Notifications\FineChargedNotification;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class FineService
{
    public function __construct(
        private PointService $pointService,
        private PointSettingService $pointSettingService,
    ) {}

    public function listFines(array $filters): LengthAwarePaginator
    {
        try {
            $query = LateFine::with(['borrowing.member', 'borrowing.bookInstance.book']);

            if (isset($filters['is_paid'])) {
                $query->where('is_paid', $filters['is_paid'] === 'true');
            }

            if (! empty($filters['member_id'])) {
                $query->whereHas('borrowing', fn ($q) => $q->where('member_id', $filters['member_id']));
            }

            $perPage = min(100, max(1, (int) ($filters['per_page'] ?? 15)));

            return $query->orderByDesc('id')->paginate($perPage);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function memberHasUnpaidFines(int $memberId): bool
    {
        return LateFine::whereHas('borrowing', fn ($q) => $q->where('member_id', $memberId))
            ->where('is_paid', false)
            ->exists();
    }

    public function assertMemberHasNoUnpaidFines(int $memberId): void
    {
        if ($this->memberHasUnpaidFines($memberId)) {
            throw new \Exception('لدى العضو غرامة غير مدفوعة، يرجى تسويتها أولاً قبل أي استعارة أو حجز أو شراء');
        }
    }

    public function payFine(int $fineId, string $method = 'points'): LateFine
    {
        return DB::transaction(function () use ($fineId, $method) {
            $fine = LateFine::with('borrowing.member')->lockForUpdate()->find($fineId);
            if (! $fine) {
                throw new \Exception('الغرامة غير موجودة');
            }
            if ($fine->is_paid) {
                throw new \Exception('تم دفع هذه الغرامة مسبقاً');
            }

            $method = in_array($method, ['points', 'cash'], true) ? $method : 'points';

            return $method === 'cash'
                ? $this->settleFineFully($fine, 'cash')
                : $this->applyPointsTowardFine($fine);
        });
    }

    public function settleUnpaidFinesFromBalance(int $memberId): int
    {
        $fines = LateFine::whereHas('borrowing', fn ($q) => $q->where('member_id', $memberId))
            ->where('is_paid', false)
            ->orderBy('id')
            ->get();

        $touched = 0;
        foreach ($fines as $fine) {
            if ($this->pointService->getBalance($memberId) <= 0) {
                break;
            }
            try {
                $this->payFine($fine->id, 'points');
                $touched++;
            } catch (\Exception $e) {
                break;
            }
        }

        return $touched;
    }

    public function createReplacementFine(Borrowing $borrowing, string $type): LateFine
    {
        $type = in_array($type, ['damage', 'loss'], true) ? $type : 'damage';

        $existing = LateFine::where('borrowing_id', $borrowing->id)
            ->whereIn('type', ['damage', 'loss'])
            ->first();
        if ($existing) {
            return $existing;
        }

        $this->waiveUnpaidLateFine($borrowing);

        $book = $borrowing->bookInstance?->book;
        $syp = (float) ($book?->price ?? 0);
        $points = (int) ($book?->price_points ?? 0);
        if ($points <= 0 && $syp > 0) {
            $points = $this->pointService->sypToPoints($syp);
        }

        $fine = LateFine::create([
            'borrowing_id' => $borrowing->id,
            'type' => $type,
            'days_late' => 0,
            'fine' => $syp,
            'fine_points' => $points,
            'is_paid' => $points <= 0 && $syp <= 0,
            'paid_at' => ($points <= 0 && $syp <= 0) ? now() : null,
        ]);

        if (! $fine->is_paid) {
            try {
                $fine = $this->applyPointsTowardFine($fine->fresh(['borrowing.member']));
            } catch (\Exception $e) {
                // يبقى المبلغ مستحقاً إذا لم يكفِ الرصيد
            }
        }

        $fine = $fine->fresh(['borrowing.member', 'borrowing.bookInstance.book']);
        $this->notifySafely($borrowing, new DamageFineNotification($fine));

        return $fine;
    }

    public function accrueOverdueFines(): int
    {
        $borrowings = Borrowing::with(['member', 'bookInstance.book', 'lateFine'])
            ->whereNull('returned_at')
            ->whereDate('end_date', '<', now()->toDateString())
            ->get();

        $count = 0;
        foreach ($borrowings as $borrowing) {
            if ($this->accrueBorrowing($borrowing) > 0) {
                $count++;
            }
        }

        return $count;
    }

    public function accrueBorrowing(Borrowing $borrowing): int
    {
        return (int) DB::transaction(function () use ($borrowing) {
            $borrowing = Borrowing::with(['member', 'bookInstance.book', 'lateFine'])
                ->lockForUpdate()
                ->find($borrowing->id);

            if (! $borrowing || $borrowing->isReturned() || ! $borrowing->end_date) {
                return 0;
            }

            $today = now()->startOfDay();
            $due = $borrowing->end_date->copy()->startOfDay();
            if ($due->gte($today)) {
                return 0;
            }

            $fine = $borrowing->lateFine;
            $accruedUntil = $fine?->accrued_until
                ? Carbon::parse($fine->accrued_until)->startOfDay()
                : $due;

            if ($accruedUntil->gte($today)) {
                return 0;
            }

            $daysToAccrue = (int) max(0, $accruedUntil->diffInDays($today, true));
            $charged = 0;
            for ($i = 0; $i < $daysToAccrue; $i++) {
                $day = $accruedUntil->copy()->addDays($i + 1);
                $this->chargeOneLateDay($borrowing, $day);
                $charged++;
            }

            return $charged;
        });
    }

    private function chargeOneLateDay(Borrowing $borrowing, Carbon $day): void
    {
        [$sypPerDay, $pointsPerDay] = $this->dailyFineRates($borrowing);
        if ($sypPerDay <= 0 && $pointsPerDay <= 0) {
            $this->touchAccrual($borrowing, $day, 0, 0, true);

            return;
        }

        $memberId = $borrowing->member_id;
        $balance = $this->pointService->getBalance($memberId);
        $paidFromPoints = $pointsPerDay > 0 && $balance >= $pointsPerDay;

        if ($paidFromPoints) {
            $fine = $this->touchAccrual($borrowing, $day, 0, 0, true);
            $this->pointService->debit(
                $memberId,
                $pointsPerDay,
                'spend',
                LateFine::class,
                (string) $fine->id,
                'خصم غرامة تأخير يومي'
            );
            $this->notifySafely($borrowing, new FineChargedNotification($fine->fresh(['borrowing.bookInstance.book']), $pointsPerDay, $sypPerDay));

            return;
        }

        $fine = $this->touchAccrual($borrowing, $day, $sypPerDay, $pointsPerDay, false);
        $this->notifySafely($borrowing, new FineAccumulatedNotification($fine, $pointsPerDay, $sypPerDay));
    }

    private function dailyFineRates(Borrowing $borrowing): array
    {
        $sypPerDay = $this->pointSettingService->getFinePerDaySyp();
        $pointsPerDay = $this->pointSettingService->getFinePerDayPoints();
        if ($pointsPerDay <= 0 && $sypPerDay > 0) {
            $pointsPerDay = $this->pointService->sypToPoints($sypPerDay);
        }

        $maxSyp = $borrowing->bookInstance?->book?->price;
        if ($maxSyp !== null) {
            $already = (float) ($borrowing->lateFine?->fine ?? 0);
            $remainingCap = max(0, (float) $maxSyp - $already);
            $sypPerDay = min($sypPerDay, $remainingCap);
        }

        $maxPoints = (int) ($borrowing->bookInstance?->book?->price_points ?? 0);
        if ($maxPoints > 0) {
            $alreadyPoints = (int) ($borrowing->lateFine?->fine_points ?? 0);
            $remainingPoints = max(0, $maxPoints - $alreadyPoints);
            $pointsPerDay = min($pointsPerDay, $remainingPoints);
        }

        return [$sypPerDay, $pointsPerDay];
    }

    private function waiveUnpaidLateFine(Borrowing $borrowing): void
    {
        LateFine::where('borrowing_id', $borrowing->id)
            ->where(function ($query) {
                $query->where('type', 'late')->orWhereNull('type');
            })
            ->where('is_paid', false)
            ->update([
                'is_paid' => true,
                'paid_at' => now(),
                'paid_via' => 'waived',
            ]);
    }

    private function touchAccrual(Borrowing $borrowing, Carbon $day, float $addSyp, int $addPoints, bool $dayPaid): LateFine
    {
        $fine = $borrowing->lateFine;
        if (! $fine) {
            $fine = LateFine::create([
                'borrowing_id' => $borrowing->id,
                'type' => 'late',
                'days_late' => 1,
                'fine' => max(0, $addSyp),
                'fine_points' => max(0, (int) $addPoints),
                'is_paid' => $dayPaid,
                'paid_at' => $dayPaid ? now() : null,
                'paid_via' => $dayPaid ? 'points' : null,
                'accrued_until' => $day->toDateString(),
            ]);
            $borrowing->setRelation('lateFine', $fine);

            return $fine->fresh();
        }

        $unpaidSyp = $fine->is_paid ? 0 : (float) $fine->fine;
        $unpaidPoints = $fine->is_paid ? 0 : (int) $fine->fine_points;
        $unpaidSyp += $addSyp;
        $unpaidPoints += $addPoints;
        $stillUnpaid = $unpaidSyp > 0 || $unpaidPoints > 0;
        $paidVia = $fine->paid_via;
        if ($dayPaid && $paidVia && $paidVia !== 'points') {
            $paidVia = 'mixed';
        } elseif ($dayPaid && ! $paidVia) {
            $paidVia = 'points';
        }

        $fine->update([
            'days_late' => (int) $fine->days_late + 1,
            'fine' => max(0, $unpaidSyp),
            'fine_points' => max(0, (int) $unpaidPoints),
            'is_paid' => ! $stillUnpaid,
            'paid_at' => $stillUnpaid ? null : ($fine->paid_at ?? now()),
            'paid_via' => $stillUnpaid ? $paidVia : ($paidVia ?: 'points'),
            'accrued_until' => $day->toDateString(),
        ]);
        $borrowing->setRelation('lateFine', $fine->fresh());

        return $borrowing->lateFine;
    }

    private function applyPointsTowardFine(LateFine $fine): LateFine
    {
        $owedPoints = $fine->fine_points > 0
            ? (int) $fine->fine_points
            : $this->pointService->sypToPoints((float) $fine->fine);
        $owedSyp = (float) $fine->fine;

        if ($owedPoints <= 0 && $owedSyp <= 0) {
            return $this->settleFineFully($fine, 'points');
        }

        $balance = $this->pointService->getBalance($fine->borrowing->member_id);
        $apply = min($balance, max(0, $owedPoints));
        if ($apply <= 0) {
            throw new \Exception('رصيد النقاط غير كافٍ لإتمام العملية');
        }

        $this->pointService->debit(
            $fine->borrowing->member_id,
            $apply,
            'spend',
            LateFine::class,
            (string) $fine->id,
            $apply < $owedPoints ? 'دفع جزء من غرامة التأخير' : 'دفع غرامة تأخير'
        );

        $remainingPoints = max(0, $owedPoints - $apply);
        $remainingSyp = $owedPoints > 0
            ? round($owedSyp * ($remainingPoints / $owedPoints), 2)
            : 0.0;

        if ($remainingPoints <= 0 && $remainingSyp <= 0) {
            return $this->settleFineFully($fine, 'points', $owedPoints);
        }

        $fine->update([
            'fine_points' => $remainingPoints,
            'fine' => $remainingSyp,
            'is_paid' => false,
            'paid_at' => null,
            'paid_via' => $this->resolvePaidVia($fine->paid_via, 'points'),
        ]);

        return $fine->fresh(['borrowing.member']);
    }

    private function settleFineFully(LateFine $fine, string $method, ?int $finePoints = null): LateFine
    {
        $fine->update([
            'fine_points' => $finePoints ?? $fine->fine_points,
            'fine' => $method === 'cash' ? $fine->fine : 0,
            'is_paid' => true,
            'paid_at' => now(),
            'paid_via' => $this->resolvePaidVia($fine->paid_via, $method),
        ]);

        $this->markBorrowingAsPaidIfAllFinesSettled($fine->borrowing_id);

        return $fine->fresh(['borrowing.member']);
    }

    private function resolvePaidVia(?string $previous, string $method): string
    {
        if ($previous && $previous !== $method) {
            return 'mixed';
        }

        return $method;
    }

    private function notifySafely(Borrowing $borrowing, $notification): void
    {
        try {
            $borrowing->member?->notify($notification);
        } catch (\Throwable $notificationError) {
            report($notificationError);
        }
    }

    private function markBorrowingAsPaidIfAllFinesSettled(int $borrowingId): void
    {
        $unpaidFines = LateFine::where('borrowing_id', $borrowingId)
            ->where('is_paid', false)
            ->count();

        if ($unpaidFines === 0) {
            Borrowing::where('id', $borrowingId)->update([
                'is_paid' => true,
                'paid_at' => now(),
            ]);
        }
    }
}
