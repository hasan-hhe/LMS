<?php

namespace Tests\Feature\Dashboard;

use App\Models\Author;
use App\Models\Book;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderState;
use App\Models\PointTransaction;
use App\Models\Publisher;
use App\Models\User;
use App\Models\UserPoint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $member;
    private Book $book;
    private OrderState $pendingState;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin        = User::factory()->create(['role' => 'ADMIN', 'password_hash' => bcrypt('p')]);
        $this->member       = User::factory()->create(['role' => 'MEMBER', 'password_hash' => bcrypt('p')]);
        $this->pendingState = OrderState::create(['state' => 'pending']);
        OrderState::create(['state' => 'confirmed']);

        $author   = Author::create(['firstname' => 'م', 'lastname' => 'ن', 'nationality' => 'أ']);
        $category = Category::create(['title' => 'عام', 'discription' => 'وصف']);
        $pub      = Publisher::create(['name' => 'نشر', 'location' => 'مكان']);

        $this->book = Book::create([
            'ISBN' => '978-order-test', 'auther_id' => $author->id, 'catagory_id' => $category->id,
            'publisher_id' => $pub->id, 'title' => 'ك', 'discription' => 'و', 'price' => 50,
            'price_points' => 1, 'amount' => 10, 'rate_avg' => 0, 'cover_url' => '',
            'year_of_publishing' => '2020', 'number_edition' => '1',
        ]);
        UserPoint::create(['user_id' => $this->member->id, 'balance' => 500]);
    }

    public function test_store_creates_order(): void
    {
        $token = $this->admin->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/orders', [
                'user_id' => $this->member->id,
                'items'   => [
                    ['isbn' => $this->book->ISBN, 'count' => 2],
                ],
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.total_amount', 2)
            ->assertJsonPath('data.total_prices', 100);
    }

    public function test_store_fails_with_invalid_isbn(): void
    {
        $token = $this->admin->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/orders', [
                'user_id' => $this->member->id,
                'items'   => [['isbn' => 'non-existent', 'count' => 1]],
            ])
            ->assertStatus(422);
    }

    public function test_show_returns_order(): void
    {
        $order = Order::create([
            'user_id'      => $this->member->id,
            'state_id'     => $this->pendingState->id,
            'total_prices' => 100,
            'total_amount' => 2,
        ]);

        $token = $this->admin->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson("/api/v1/orders/{$order->id}")
            ->assertStatus(200);
    }

    public function test_update_state_changes_order_state(): void
    {
        $confirmedState = OrderState::where('state', 'confirmed')->first();
        $order          = Order::create([
            'user_id'      => $this->member->id,
            'state_id'     => $this->pendingState->id,
            'total_prices' => 100,
            'total_points' => 2,
            'total_amount' => 2,
        ]);
        OrderItem::create([
            'order_id' => $order->id,
            'book_ISBN' => $this->book->ISBN,
            'price_once' => $this->book->price,
            'count' => 2,
        ]);

        $token = $this->admin->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson("/api/v1/orders/{$order->id}/state", ['state_id' => $confirmedState->id])
            ->assertStatus(200);

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'state_id' => $confirmedState->id]);
        $this->assertSame(8, $this->book->fresh()->amount);
    }

    public function test_confirm_fails_without_sufficient_stock_before_points_debit(): void
    {
        $confirmedState = OrderState::where('state', 'confirmed')->first();
        $order = Order::create([
            'user_id' => $this->member->id,
            'state_id' => $this->pendingState->id,
            'total_prices' => 550,
            'total_points' => 11,
            'total_amount' => 11,
        ]);
        OrderItem::create([
            'order_id' => $order->id,
            'book_ISBN' => $this->book->ISBN,
            'price_once' => $this->book->price,
            'count' => 11,
        ]);

        $token = $this->admin->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson("/api/v1/orders/{$order->id}/state", ['state_id' => $confirmedState->id])
            ->assertStatus(422)
            ->assertJsonPath('body', 'الكمية المتاحة من الكتاب ك غير كافية');

        $this->assertSame(10, $this->book->fresh()->amount);
        $this->assertSame(500, UserPoint::where('user_id', $this->member->id)->value('balance'));
        $this->assertFalse(PointTransaction::where('reference_id', (string) $order->id)->exists());
        $this->assertSame($this->pendingState->id, $order->fresh()->state_id);
    }
}
