<?php

namespace Tests\Feature\Dashboard;

use App\Models\Author;
use App\Models\Book;
use App\Models\BookInstance;
use App\Models\Borrowing;
use App\Models\Category;
use App\Models\InstanceState;
use App\Models\LateFine;
use App\Models\Publisher;
use App\Models\User;
use App\Models\UserPoint;
use App\Services\FineService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class FineAccrualTest extends TestCase
{
    use RefreshDatabase;

    public function test_daily_accrual_debits_points_when_balance_is_enough(): void
    {
        Notification::fake();
        [$member, $borrowing] = $this->overdueBorrowing(balance: 10, daysOverdue: 2);

        $this->artisan('fines:accrue-daily')->assertSuccessful();

        $this->assertSame(8, UserPoint::where('user_id', $member->id)->value('balance'));
        $this->assertDatabaseHas('late_fines', [
            'borrowing_id' => $borrowing->id,
            'days_late' => 2,
            'is_paid' => true,
        ]);
    }

    public function test_daily_accrual_accumulates_when_balance_is_empty(): void
    {
        Notification::fake();
        [$member, $borrowing] = $this->overdueBorrowing(balance: 0, daysOverdue: 3);

        app(FineService::class)->accrueOverdueFines();

        $fine = LateFine::where('borrowing_id', $borrowing->id)->first();
        $this->assertNotNull($fine);
        $this->assertFalse($fine->is_paid);
        $this->assertSame(3, (int) $fine->days_late);
        $this->assertSame(3, (int) $fine->fine_points);
        $this->assertSame(0, UserPoint::where('user_id', $member->id)->value('balance'));
    }

    public function test_top_up_settles_accumulated_fine_when_balance_covers_it(): void
    {
        Notification::fake();
        [$member, $borrowing] = $this->overdueBorrowing(balance: 0, daysOverdue: 2);
        app(FineService::class)->accrueOverdueFines();

        $admin = User::factory()->create(['role' => 'ADMIN']);
        $this->actingAs($member)
            ->postJson('/api/v1/member/points/top-up', [
                'code' => $this->makeTopUpCode($admin->id, 10),
            ])
            ->assertOk();

        $this->assertDatabaseHas('late_fines', [
            'borrowing_id' => $borrowing->id,
            'is_paid' => true,
            'paid_via' => 'points',
        ]);
    }

    public function test_partial_top_up_reduces_remaining_fine(): void
    {
        Notification::fake();
        [$member, $borrowing] = $this->overdueBorrowing(balance: 0, daysOverdue: 10);
        app(FineService::class)->accrueOverdueFines();

        $fine = LateFine::where('borrowing_id', $borrowing->id)->first();
        $this->assertSame(10, (int) $fine->fine_points);

        $admin = User::factory()->create(['role' => 'ADMIN']);
        $this->actingAs($member)
            ->postJson('/api/v1/member/points/top-up', [
                'code' => $this->makeTopUpCode($admin->id, 4),
            ])
            ->assertOk();

        $fine->refresh();
        $this->assertFalse($fine->is_paid);
        $this->assertSame(6, (int) $fine->fine_points);
        $this->assertSame(0, UserPoint::where('user_id', $member->id)->value('balance'));
    }

    /**
     * @return array{0: User, 1: Borrowing}
     */
    private function overdueBorrowing(int $balance, int $daysOverdue): array
    {
        $librarian = User::factory()->create(['role' => 'LIBRARIAN']);
        $member = User::factory()->create(['role' => 'MEMBER']);
        UserPoint::create(['user_id' => $member->id, 'balance' => $balance]);

        $state = InstanceState::create(['state' => 'borrowed']);
        $author = Author::create(['firstname' => 'م', 'lastname' => 'ن', 'nationality' => 'أ']);
        $category = Category::create(['title' => 'عام', 'discription' => 'وصف']);
        $pub = Publisher::create(['name' => 'نشر', 'location' => 'مكان']);
        $book = Book::create([
            'ISBN' => '978-accrue-'.$member->id, 'auther_id' => $author->id, 'catagory_id' => $category->id,
            'publisher_id' => $pub->id, 'title' => 'ك', 'discription' => 'و', 'price' => 20,
            'amount' => 1, 'rate_avg' => 0, 'cover_url' => '', 'year_of_publishing' => '2020', 'number_edition' => '1',
        ]);
        $instance = BookInstance::create(['book_ISBN' => $book->ISBN, 'state_id' => $state->id, 'condition' => 'new']);

        $borrowing = Borrowing::create([
            'member_id' => $member->id,
            'librarian_id' => $librarian->id,
            'book_instance_id' => $instance->id,
            'start_date' => now()->subDays($daysOverdue + 7),
            'end_date' => now()->subDays($daysOverdue),
            'due_date' => now()->subDays($daysOverdue),
            'borrowing_cast' => 0,
            'is_paid' => false,
        ]);

        return [$member, $borrowing];
    }

    private function makeTopUpCode(int $adminId, int $points): string
    {
        $code = 'LMS-PTS-ACCRUE1';
        \App\Models\TopUpCode::create([
            'code' => $code,
            'points_value' => $points,
            'created_by' => $adminId,
        ]);

        return $code;
    }
}
