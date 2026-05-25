<?php

namespace Database\Seeders;

use App\Models\BookInstance;
use App\Models\Borrowing;
use App\Models\InstanceState;
use App\Models\LateFine;
use App\Models\Reservation;
use App\Models\ReservationState;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DemoCirculationSeeder extends Seeder
{
    public function run(): void
    {
        DB::beginTransaction();
        try {
            Borrowing::query()->delete();
            LateFine::query()->delete();
            Reservation::query()->delete();

            $librarian = User::where('email', 'librarian@lms.test')->firstOrFail();
            $member1   = User::where('email', 'member1@lms.test')->firstOrFail();
            $member2   = User::where('email', 'member2@lms.test')->firstOrFail();
            $member3   = User::where('email', 'member3@lms.test')->firstOrFail();

            $borrowedState  = InstanceState::where('state', 'borrowed')->firstOrFail();
            $availableState = InstanceState::where('state', 'available')->firstOrFail();

            $borrowedInstances = BookInstance::where('state_id', $borrowedState->id)->get();

            $activeBorrowing1 = Borrowing::create([
                'member_id'        => $member1->id,
                'librarian_id'     => $librarian->id,
                'book_instance_id' => $borrowedInstances[0]->id,
                'start_date'       => now()->subDays(5)->toDateString(),
                'end_date'         => now()->addDays(14)->toDateString(),
                'returned_at'      => null,
                'due_date'         => now()->addDays(14)->toDateString(),
                'borrowing_cast'   => 0,
                'is_paid'          => true,
                'paid_at'          => now(),
            ]);

            $activeBorrowing2 = Borrowing::create([
                'member_id'        => $member2->id,
                'librarian_id'     => $librarian->id,
                'book_instance_id' => $borrowedInstances[1]->id,
                'start_date'       => now()->subDays(3)->toDateString(),
                'end_date'         => now()->addDays(7)->toDateString(),
                'returned_at'      => null,
                'due_date'         => now()->addDays(7)->toDateString(),
                'borrowing_cast'   => 0,
                'is_paid'          => true,
                'paid_at'          => now(),
            ]);

            $overdueUnpaid = Borrowing::create([
                'member_id'        => $member3->id,
                'librarian_id'     => $librarian->id,
                'book_instance_id' => $borrowedInstances[2]->id,
                'start_date'       => now()->subDays(20)->toDateString(),
                'end_date'         => now()->subDays(5)->toDateString(),
                'returned_at'      => null,
                'due_date'         => now()->subDays(5)->toDateString(),
                'borrowing_cast'   => 0,
                'is_paid'          => true,
                'paid_at'          => now()->subDays(20),
            ]);

            LateFine::create([
                'borrowing_id' => $overdueUnpaid->id,
                'days_late'    => 5,
                'fine'         => 25.00,
                'is_paid'      => false,
                'paid_at'      => null,
            ]);

            $overdueInstance = BookInstance::where('state_id', $availableState->id)->firstOrFail();
            $overdueInstance->update(['state_id' => $borrowedState->id]);

            $overduePaid = Borrowing::create([
                'member_id'        => $member1->id,
                'librarian_id'     => $librarian->id,
                'book_instance_id' => $overdueInstance->id,
                'start_date'       => now()->subDays(25)->toDateString(),
                'end_date'         => now()->subDays(10)->toDateString(),
                'returned_at'      => null,
                'due_date'         => now()->subDays(10)->toDateString(),
                'borrowing_cast'   => 0,
                'is_paid'          => true,
                'paid_at'          => now()->subDays(25),
            ]);

            LateFine::create([
                'borrowing_id' => $overduePaid->id,
                'days_late'    => 10,
                'fine'         => 50.00,
                'is_paid'      => true,
                'paid_at'      => now()->subDays(2),
            ]);

            $returnedInstance = BookInstance::where('state_id', $availableState->id)->firstOrFail();

            Borrowing::create([
                'member_id'        => $member2->id,
                'librarian_id'     => $librarian->id,
                'book_instance_id' => $returnedInstance->id,
                'start_date'       => now()->subDays(30)->toDateString(),
                'end_date'         => now()->subDays(10)->toDateString(),
                'returned_at'      => now()->subDays(8),
                'due_date'         => now()->subDays(10)->toDateString(),
                'borrowing_cast'   => 0,
                'is_paid'          => true,
                'paid_at'          => now()->subDays(30),
            ]);

            $returnedInstance->update(['state_id' => $availableState->id]);

            $pendingReservationState   = ReservationState::where('state', 'pending')->firstOrFail();
            $cancelledReservationState = ReservationState::where('state', 'cancelled')->firstOrFail();
            $reservedInstanceState     = InstanceState::where('state', 'reserved')->firstOrFail();

            $reservedInstance = BookInstance::where('state_id', $reservedInstanceState->id)->firstOrFail();

            Reservation::create([
                'user_id'          => $member3->id,
                'book_instance_id' => $reservedInstance->id,
                'state_id'         => $pendingReservationState->id,
                'cause'            => 'الكتاب غير متاح حالياً',
                'notified_at'      => null,
                'reserved_at'      => now()->subDays(2),
            ]);

            $cancelInstance = BookInstance::where('state_id', $availableState->id)->firstOrFail();

            Reservation::create([
                'user_id'          => $member1->id,
                'book_instance_id' => $cancelInstance->id,
                'state_id'         => $cancelledReservationState->id,
                'cause'            => 'طلب إلغاء من العضو',
                'notified_at'      => null,
                'reserved_at'      => now()->subDays(10),
            ]);

            unset($activeBorrowing1, $activeBorrowing2);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
