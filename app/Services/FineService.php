<?php

namespace App\Services;

use App\Models\Borrowing;
use App\Models\LateFine;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class FineService
{
    public function __construct(private PointService $pointService) {}

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

            return $query->orderByDesc('id')->paginate(15);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function payFine(int $fineId): LateFine
    {
        DB::beginTransaction();
        try {
            $fine = LateFine::with('borrowing.member')->lockForUpdate()->find($fineId);
            if (! $fine) {
                throw new \Exception('الغرامة غير موجودة');
            }
            if ($fine->is_paid) {
                throw new \Exception('تم دفع هذه الغرامة مسبقاً');
            }

            $finePoints = $fine->fine_points ?: $this->pointService->sypToPoints((float) $fine->fine);
            if ($finePoints > 0) {
                $this->pointService->debit(
                    $fine->borrowing->member_id,
                    $finePoints,
                    'spend',
                    LateFine::class,
                    (string) $fine->id,
                    'دفع غرامة تأخير'
                );
            }

            $fine->update([
                'fine_points' => $finePoints,
                'is_paid' => true,
                'paid_at' => now(),
            ]);

            $this->markBorrowingAsPaidIfAllFinesSettled($fine->borrowing_id);

            DB::commit();

            return $fine->fresh(['borrowing.member']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
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
