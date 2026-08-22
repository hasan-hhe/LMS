<?php

namespace App\Services;

use App\Models\BookInstance;
use App\Models\Borrowing;
use App\Models\BorrowingEdition;
use App\Models\InstanceState;
use App\Models\Reservation;
use App\Models\User;
use App\Repositories\Interfaces\BorrowingRepositoryInterface;
use Illuminate\Support\Facades\DB;

class BorrowingService
{
    public const MAX_ACTIVE_BORROWINGS = 1;

    public function __construct(
        private BorrowingRepositoryInterface $borrowingRepository,
        private PointService $pointService,
        private PointSettingService $pointSettingService,
        private FineService $fineService,
    ) {}

    public function checkoutBook(array $data, int $librarianId): Borrowing
    {
        DB::beginTransaction();
        try {
            $member = $this->findAndValidateMember($data['member_id']);
            $instance = $this->findAvailableInstance($data['book_instance_id']);

            $this->validateMemberBorrowingLimit($member->id);
            $this->fineService->assertMemberHasNoUnpaidFines($member->id);
            $this->assertMemberHasNoActiveReservation($member->id);

            $data['end_date'] = $this->resolveDueDate($instance, $data['end_date'] ?? null);
            $borrowing = $this->createBorrowingRecord($member->id, $librarianId, $instance, $data);
            $this->markInstanceAsBorrowed($instance);

            DB::commit();

            return $borrowing->load(['member', 'librarian', 'bookInstance.book']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function checkoutFromReservation(Reservation $reservation, int $librarianId, ?string $endDate = null): Borrowing
    {
        $member = $this->findAndValidateMember($reservation->user_id);
        $instance = $reservation->bookInstance;
        if (! $instance) {
            throw new \Exception('نسخة الكتاب غير موجودة');
        }

        $this->validateMemberBorrowingLimit($member->id);
        $this->fineService->assertMemberHasNoUnpaidFines($member->id);

        $state = $instance->state?->state;
        if (! in_array($state, ['available', 'reserved'], true)) {
            throw new \Exception('نسخة الكتاب غير متاحة للاستلام حالياً');
        }

        $data = [
            'end_date' => $this->resolveDueDate($instance, $endDate),
        ];

        $borrowing = $this->createBorrowingRecord($member->id, $librarianId, $instance, $data);
        $this->markInstanceAsBorrowed($instance);

        return $borrowing->load(['member', 'librarian', 'bookInstance.book']);
    }

    public function returnBook(int $borrowingId, array $data = []): Borrowing
    {
        DB::beginTransaction();
        try {
            $borrowing = $this->findActiveBorrowing($borrowingId);
            $outcome = $data['outcome'] ?? 'ok';

            $this->fineService->accrueBorrowing($borrowing);

            if (in_array($outcome, ['damaged', 'lost'], true)) {
                $this->fineService->createReplacementFine($borrowing, $outcome === 'lost' ? 'loss' : 'damage');
                $this->setInstanceState($borrowing->bookInstance, $outcome);
            } else {
                $this->releaseInstanceAfterReturn($borrowing->bookInstance);
            }

            $borrowing->update(['returned_at' => now()]);

            $fresh = $borrowing->fresh(['member', 'bookInstance.book', 'lateFine', 'damageFine']);
            if ($fresh && ! $fresh->lateFine && ! $fresh->damageFine && $outcome === 'ok') {
                $reward = $this->pointSettingService->getRewardReturnOnTime();
                if ($reward > 0) {
                    $this->pointService->credit($borrowing->member_id, $reward, 'reward', Borrowing::class, (string) $borrowing->id, 'مكافأة إعادة الكتاب في الموعد');
                }
            }

            DB::commit();

            return $borrowing->fresh(['member', 'bookInstance.book', 'lateFine', 'damageFine']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function extendBorrowing(int $borrowingId, array $data, bool $administrative = false): Borrowing
    {
        DB::beginTransaction();
        try {
            $borrowing = $this->findActiveBorrowing($borrowingId);

            if (! $administrative) {
                $this->validateExtensionEligibility($borrowing);
                $this->fineService->assertMemberHasNoUnpaidFines($borrowing->member_id);
            }

            $extensionPoints = $administrative
                ? 0
                : $this->calculateExtensionPoints($borrowing, $data['new_end_date']);

            if ($this->extensionDays($borrowing, $data['new_end_date']) < 1) {
                throw new \Exception('تاريخ التمديد يجب أن يكون بعد تاريخ انتهاء الاستعارة الحالي');
            }

            BorrowingEdition::create([
                'borrowing_id' => $borrowing->id,
                'new_end_date' => $data['new_end_date'],
                'taxe' => $extensionPoints,
                'cause' => $data['cause'] ?? ($administrative ? 'تمديد إداري' : null),
            ]);

            if ($extensionPoints > 0) {
                $this->pointService->debit($borrowing->member_id, $extensionPoints, 'spend', BorrowingEdition::class, (string) $borrowing->id, 'رسوم تمديد الاستعارة بالنقاط');
            }

            $borrowing->update([
                'end_date' => $data['new_end_date'],
                'due_date' => $data['new_end_date'],
            ]);

            DB::commit();

            return $borrowing->fresh(['member', 'bookInstance.book', 'editions']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function quoteExtension(int $borrowingId, ?string $newEndDate = null): array
    {
        $borrowing = Borrowing::with(['editions', 'bookInstance.book'])->find($borrowingId);
        if (! $borrowing) {
            throw new \Exception('الاستعارة غير موجودة');
        }

        $defaultEnd = $borrowing->end_date
            ? $borrowing->end_date->copy()->addDays(7)->toDateString()
            : now()->addDays(7)->toDateString();
        $target = $newEndDate ?: $defaultEnd;
        $pointsPerDay = $this->pointSettingService->getExtensionPerDayPoints();
        $days = $this->extensionDays($borrowing, $target);
        $points = $days * $pointsPerDay;
        $alreadyExtended = $borrowing->editions()->exists();
        $overdue = $borrowing->end_date?->isPast() ?? false;
        $returned = $borrowing->isReturned();
        $unpaidFines = $this->fineService->memberHasUnpaidFines($borrowing->member_id);
        $minNewEnd = $borrowing->end_date?->copy()->addDay()->toDateString();

        $canExtend = ! $returned && ! $overdue && ! $alreadyExtended && ! $unpaidFines && $days >= 1;
        $reason = null;
        if ($returned) {
            $reason = 'تم إعادة هذا الكتاب مسبقاً';
        } elseif ($overdue) {
            $reason = 'لا يمكن تمديد استعارة منتهية الصلاحية';
        } elseif ($alreadyExtended) {
            $reason = 'تم تمديد هذه الاستعارة مسبقاً. يمكن للأمين التمديد إدارياً';
        } elseif ($unpaidFines) {
            $reason = 'لدى العضو غرامة غير مدفوعة';
        } elseif ($days < 1) {
            $reason = 'تاريخ التمديد يجب أن يكون بعد تاريخ انتهاء الاستعارة الحالي';
        }

        return [
            'can_extend' => $canExtend,
            'already_extended' => $alreadyExtended,
            'points' => $points,
            'points_per_day' => $pointsPerDay,
            'days' => $days,
            'current_end_date' => $borrowing->end_date?->toDateString(),
            'min_new_end_date' => $minNewEnd,
            'new_end_date' => $target,
            'reason' => $reason,
        ];
    }

    private function createBorrowingRecord(int $memberId, int $librarianId, BookInstance $instance, array $data): Borrowing
    {
        $instance->loadMissing('book');
        $borrowPoints = max(0, (int) ($instance->book?->borrow_points ?? 0));

        $borrowing = $this->borrowingRepository->create([
            'member_id' => $memberId,
            'librarian_id' => $librarianId,
            'book_instance_id' => $instance->id,
            'start_date' => now()->toDateString(),
            'end_date' => $data['end_date'],
            'due_date' => $data['end_date'],
            'borrowing_cast' => $borrowPoints,
            'is_paid' => false,
        ]);

        if ($borrowPoints > 0) {
            $this->pointService->debit($memberId, $borrowPoints, 'spend', Borrowing::class, (string) $borrowing->id, 'رسوم استعارة كتاب');
            $borrowing->update(['is_paid' => true, 'paid_at' => now()]);
        }

        return $borrowing;
    }

    private function resolveDueDate(BookInstance $instance, ?string $endDate = null): string
    {
        if (is_string($endDate) && $endDate !== '') {
            return $endDate;
        }

        $instance->loadMissing('book');
        $fallback = $this->pointSettingService->getLoanPeriodDays();
        $days = $instance->book?->loanPeriodDays($fallback) ?? max(1, $fallback);

        return now()->addDays($days)->toDateString();
    }

    private function findAndValidateMember(int $memberId): User
    {
        $member = User::where('id', $memberId)->where('role', 'MEMBER')->first();
        if (! $member) {
            throw new \Exception('العضو غير موجود أو ليس عضواً نشطاً');
        }
        if ($member->participe_end_date && $member->participe_end_date->isPast()) {
            throw new \Exception('انتهت صلاحية عضوية هذا الحساب');
        }

        return $member;
    }

    private function findAvailableInstance(int $instanceId): BookInstance
    {
        $instance = BookInstance::with(['state', 'book'])->find($instanceId);
        if (! $instance) {
            throw new \Exception('نسخة الكتاب غير موجودة');
        }

        $state = $instance->state?->state;
        if ($state === 'borrowed') {
            throw new \Exception('لا يمكن استعارة نسخة معارة حالياً');
        }
        if ($state === 'reserved') {
            throw new \Exception('لا يمكن استعارة نسخة محجوزة. استخدم تسليم الحجز');
        }
        if ($state !== 'available') {
            throw new \Exception('نسخة الكتاب غير متاحة للاستعارة حالياً');
        }

        return $instance;
    }

    private function validateMemberBorrowingLimit(int $memberId): void
    {
        $activeCount = $this->borrowingRepository->getActiveBorrowingsCount($memberId);
        if ($activeCount >= self::MAX_ACTIVE_BORROWINGS) {
            throw new \Exception('لا يمكن استعارة أكثر من نسخة واحدة قبل إعادة النسخة الحالية');
        }
    }

    private function assertMemberHasNoActiveReservation(int $memberId): void
    {
        $active = Reservation::where('user_id', $memberId)
            ->whereHas('state', fn ($q) => $q->whereIn('state', ['pending', 'ready']))
            ->exists();

        if ($active) {
            throw new \Exception('لدى العضو حجز نشط. يرجى استلامه أو إلغاؤه قبل تسجيل استعارة جديدة');
        }
    }

    private function markInstanceAsBorrowed(BookInstance $instance): void
    {
        $this->setInstanceState($instance, 'borrowed');
    }

    private function releaseInstanceAfterReturn(BookInstance $instance): void
    {
        $held = Reservation::where('book_instance_id', $instance->id)
            ->whereHas('state', fn ($q) => $q->whereIn('state', ['pending', 'ready']))
            ->exists();

        $this->setInstanceState($instance, $held ? 'reserved' : 'available');
    }

    private function setInstanceState(BookInstance $instance, string $stateName): void
    {
        $state = InstanceState::where('state', $stateName)->first();
        if ($state) {
            $instance->update(['state_id' => $state->id]);
        }
    }

    private function findActiveBorrowing(int $borrowingId): Borrowing
    {
        $borrowing = Borrowing::with(['bookInstance', 'member'])->find($borrowingId);
        if (! $borrowing) {
            throw new \Exception('الاستعارة غير موجودة');
        }
        if ($borrowing->isReturned()) {
            throw new \Exception('تم إعادة هذا الكتاب مسبقاً');
        }

        return $borrowing;
    }

    private function validateExtensionEligibility(Borrowing $borrowing): void
    {
        if ($borrowing->editions()->exists()) {
            throw new \Exception('لا يمكن تمديد الاستعارة أكثر من مرة واحدة');
        }
        if ($borrowing->end_date->isPast()) {
            throw new \Exception('لا يمكن تمديد استعارة منتهية الصلاحية');
        }
    }

    private function calculateExtensionPoints(Borrowing $borrowing, string $newEndDate): int
    {
        $extraDays = $this->extensionDays($borrowing, $newEndDate);
        if ($extraDays < 1) {
            return 0;
        }

        return $extraDays * $this->pointSettingService->getExtensionPerDayPoints();
    }

    private function extensionDays(Borrowing $borrowing, string $newEndDate): int
    {
        if (! $borrowing->end_date) {
            return 0;
        }

        $currentEnd = $borrowing->end_date->copy()->startOfDay();
        $target = now()->parse($newEndDate)->startOfDay();
        if ($target->lte($currentEnd)) {
            return 0;
        }

        return (int) $currentEnd->diffInDays($target, false);
    }
}
