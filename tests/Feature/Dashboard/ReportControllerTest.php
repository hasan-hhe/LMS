<?php

namespace Tests\Feature\Dashboard;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'ADMIN', 'password_hash' => bcrypt('p')]);
    }

    public function test_overdue_report_returns_data(): void
    {
        $token = $this->admin->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/reports/overdue')
            ->assertStatus(200)
            ->assertJsonStructure(['data' => ['total', 'borrowings']]);
    }

    public function test_stats_report_returns_data(): void
    {
        $token = $this->admin->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/reports/stats')
            ->assertStatus(200)
            ->assertJsonStructure(['data' => ['total_books', 'total_members', 'active_borrowings']]);
    }

    public function test_most_borrowed_report_returns_data(): void
    {
        $token = $this->admin->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/reports/most-borrowed')
            ->assertStatus(200)
            ->assertJsonStructure(['data' => ['books']]);
    }

    public function test_fines_summary_report_returns_data(): void
    {
        $token = $this->admin->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/reports/fines-summary')
            ->assertStatus(200)
            ->assertJsonStructure(['data' => ['total_fines', 'total_amount', 'paid_amount', 'unpaid_amount']]);
    }

    public function test_inventory_report_returns_data(): void
    {
        $token = $this->admin->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/reports/inventory')
            ->assertStatus(200)
            ->assertJsonStructure(['data' => ['total_books', 'total_instances', 'total_members']]);
    }

    public function test_reports_require_authentication(): void
    {
        $this->getJson('/api/v1/reports/stats')->assertStatus(401);
    }
}
