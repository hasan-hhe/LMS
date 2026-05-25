<?php

namespace App\Services;

use App\Models\BookInstance;
use App\Models\Borrowing;
use App\Models\BorrowingEdition;
use App\Models\InstanceState;
use App\Models\LateFine;
use App\Models\User;
use App\Repositories\Interfaces\BorrowingRepositoryInterface;
use Illuminate\Support\Facades\DB;

class BorrowingService
{
    private const MAX_ACTIVE_BORROWINGS = 5;
    private const FINE_PER_DAY          = 0.5;

    public function __construct(private BorrowingRepositoryInterface $borrowingRepository) {}

    public function checkoutBook(array $data, int $librarianId): Borrowing
    {
        DB::beginTransaction();
        try {
            $member   = $this->findAndValidateMember($data['member_id']);
            $instance = $this->findAndValidateInstance($data['book_instance_id']);

            $this->validateMemberBorrowingLimit($member->id);
            $this->validateMemberHasNoPendingFines($member->id);

            $borrowing = $this->borrowingRepository->create([
                'member_id'        => $member->id,
                'librarian_id'     => $librarianId,
                'book_instance_id' => $instance->id,
                'start_date'       => now()->toDateString(),
                'end_date'         => $data['end_date'],
                'due_date'         => $data['end_date'],
                'borrowing_cast'   => $data['borrowing_cost'] ?? 0,
                'is_paid'          => false,
            ]);

            $this->markInstanceAsBorrowed($instance);

            DB::commit();
            return $borrowing->load(['member', 'librarian', 'bookInstance.book']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function returnBook(int $borrowingId): Borrowing
    {
        DB::beginTransaction();
        try {
            $borrowing = $this->findActiveBorrowing($borrowingId);

            $borrowing->update(['returned_at' => now()]);

            $this->markInstanceAsAvailable($borrowing->bookInstance);
            $this->calculateAndSaveLateFine($borrowing);

            DB::commit();
            return $borrowing->fresh(['member', 'bookInstance.book', 'lateFine']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function extendBorrowing(int $borrowingId, array $data): Borrowing
    {
        DB::beginTransaction();
        try {
            $borrowing = $this->findActiveBorrowing($borrowingId);

            $this->validateExtensionEligibility($borrowing);

            $extensionTax = $this->calculateExtensionTax($borrowing, $data['new_end_date']);

            BorrowingEdition::create([
                'borrowing_id' => $borrowing->id,
                'new_end_date' => $data['new_end_date'],
                'taxe'         => $extensionTax,
                'cause'        => $data['cause'] ?? null,
            ]);

            $borrowing->update(['end_date' => $data['new_end_date']]);

            DB::commit();
            return $borrowing->fresh(['member', 'bookInstance.book', 'editions']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    private function findAndValidateMember(int $memberId): User
    {
        $member = User::where('id', $memberId)->where('role', 'MEMBER')->first();
        if (!$member) {
            throw new \Exception('العضو غير موجود أو ليس عضواً نشطاً');
        }
        if ($member->participe_end_date && $member->participe_end_date->isPast()) {
            throw new \Exception('انتهت صلاحية عضوية هذا الحساب');
        }
        return $member;
    }

    private function findAndValidateInstance(int $instanceId): BookInstance
    {
        $instance = BookInstance::with('state')->find($instanceId);
        if (!$instance) {
            throw new \Exception('نسخة الكتاب غير موجودة');
        }
        if (!$instance->isAvailable()) {
            throw new \Exception('نسخة الكتاب غير متاحة للاستعارة حالياً');
        }
        return $instance;
    }

    private function validateMemberBorrowingLimit(int $memberId): void
    {
        $activeCount = $this->borrowingRepository->getActiveBorrowingsCount($memberId);
        if ($activeCount >= self::MAX_ACTIVE_BORROWINGS) {
            throw new \Exception('وصل العضو للحد الأقصى للاستعارة (' . self::MAX_ACTIVE_BORROWINGS . ' كتب)');
        }
    }

    private function validateMemberHasNoPendingFines(int $memberId): void
    {
        $unpaidFines = LateFine::whereHas('borrowing', fn($q) => $q->where('member_id', $memberId))
            ->where('is_paid', false)
            ->exists();

        if ($unpaidFines) {
            throw new \Exception('لدى العضو غرامات غير مدفوعة، يرجى تسويتها أولاً');
        }
    }

    private function markInstanceAsBorrowed(BookInstance $instance): void
    {
        $borrowedState = InstanceState::where('state', 'borrowed')->first();
        if ($borrowedState) {
            $instance->update(['state_id' => $borrowedState->id]);
        }
    }

    private function markInstanceAsAvailable(BookInstance $instance): void
    {
        $availableState = InstanceState::where('state', 'available')->first();
        if ($availableState) {
            $instance->update(['state_id' => $availableState->id]);
        }
    }

    private function findActiveBorrowing(int $borrowingId): Borrowing
    {
        $borrowing = Borrowing::with(['bookInstance', 'member'])->find($borrowingId);
        if (!$borrowing) {
            throw new \Exception('الاستعارة غير موجودة');
        }
        if ($borrowing->isReturned()) {
            throw new \Exception('تم إعادة هذا الكتاب مسبقاً');
        }
        return $borrowing;
    }

    private function calculateAndSaveLateFine(Borrowing $borrowing): void
    {
        if (!$borrowing->isOverdue()) {
            return;
        }

        $daysLate = now()->diffInDays($borrowing->end_date);
        $fine     = $daysLate * self::FINE_PER_DAY;
        $maxFine  = $borrowing->bookInstance->book->price ?? PHP_INT_MAX;

        LateFine::create([
            'borrowing_id' => $borrowing->id,
            'days_late'    => $daysLate,
            'fine'         => min($fine, $maxFine),
            'is_paid'      => false,
        ]);
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

    private function calculateExtensionTax(Borrowing $borrowing, string $newEndDate): float
    {
        $extraDays = now()->parse($newEndDate)->diffInDays($borrowing->end_date);
        return $extraDays * self::FINE_PER_DAY;
    }
}
