<?php

namespace Tests\Feature\Dashboard;

use App\Models\Author;
use App\Models\Book;
use App\Models\BookInstance;
use App\Models\Category;
use App\Models\InstanceState;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderState;
use App\Models\PointTransaction;
use App\Models\Publisher;
use App\Models\User;
use App\Models\UserPoint;
use App\Notifications\OrderStateChangedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
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
        OrderState::create(['state' => 'delivered']);
        OrderState::create(['state' => 'cancelled']);
        OrderState::create(['state' => 'rejected']);

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
            ->assertJsonPath('body', 'الكمية المتاحة من نسخ البيع للكتاب ك غير كافية');

        $this->assertSame(10, $this->book->fresh()->amount);
        $this->assertSame(500, UserPoint::where('user_id', $this->member->id)->value('balance'));
        $this->assertFalse(PointTransaction::where('reference_id', (string) $order->id)->exists());
        $this->assertSame($this->pendingState->id, $order->fresh()->state_id);
    }

    public function test_confirmed_order_can_be_marked_delivered(): void
    {
        $confirmed = OrderState::where('state', 'confirmed')->first();
        $delivered = OrderState::where('state', 'delivered')->first();
        $order = Order::create([
            'user_id' => $this->member->id,
            'state_id' => $confirmed->id,
            'total_prices' => 50,
            'total_points' => 1,
            'total_amount' => 1,
        ]);

        $token = $this->admin->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson("/api/v1/orders/{$order->id}/state", ['state_id' => $delivered->id])
            ->assertStatus(200)
            ->assertJsonPath('data.state.state', 'delivered');
    }

    public function test_cancel_confirmed_order_refunds_points_and_restocks(): void
    {
        $confirmed = OrderState::where('state', 'confirmed')->first();
        $cancelled = OrderState::where('state', 'cancelled')->first();
        $order = Order::create([
            'user_id' => $this->member->id,
            'state_id' => $this->pendingState->id,
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
        $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson("/api/v1/orders/{$order->id}/state", ['state_id' => $confirmed->id])
            ->assertStatus(200);

        $this->assertSame(8, $this->book->fresh()->amount);
        $this->assertSame(498, UserPoint::where('user_id', $this->member->id)->value('balance'));

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson("/api/v1/orders/{$order->id}/state", [
                'state_id' => $cancelled->id,
                'reason' => 'العضو طلب الإلغاء قبل الاستلام',
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.state.state', 'cancelled')
            ->assertJsonPath('data.reason', 'العضو طلب الإلغاء قبل الاستلام');

        $this->assertSame(10, $this->book->fresh()->amount);
        $this->assertSame(500, UserPoint::where('user_id', $this->member->id)->value('balance'));
        $this->assertDatabaseHas('point_transactions', [
            'user_id' => $this->member->id,
            'reference_id' => (string) $order->id,
            'note' => 'استرداد نقاط طلب ملغى',
        ]);
    }

    public function test_cannot_deliver_pending_order(): void
    {
        $delivered = OrderState::where('state', 'delivered')->first();
        $order = Order::create([
            'user_id' => $this->member->id,
            'state_id' => $this->pendingState->id,
            'total_prices' => 50,
            'total_points' => 1,
            'total_amount' => 1,
        ]);

        $token = $this->admin->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson("/api/v1/orders/{$order->id}/state", ['state_id' => $delivered->id])
            ->assertStatus(422);
    }

    public function test_pdf_order_charges_points_without_decrementing_sale_stock(): void
    {
        $this->book->digitalAsset()->create([
            'pdf_url' => 'digital/pdfs/paid.pdf',
            'is_free' => false,
        ]);

        $token = $this->member->createToken('test')->plainTextToken;

        $create = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/member/orders', [
                'items' => [
                    ['isbn' => $this->book->ISBN, 'count' => 1, 'format' => 'pdf'],
                ],
            ]);

        $create->assertStatus(201)
            ->assertJsonPath('data.items.0.format', 'pdf')
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.status_label', 'قيد المراجعة');

        $orderId = $create->json('data.id');
        $this->assertSame(10, $this->book->fresh()->amount);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson("/api/v1/member/orders/{$orderId}/pay")
            ->assertOk()
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.status_label', 'قيد المراجعة');

        $this->assertSame(10, $this->book->fresh()->amount);
        $this->assertSame(500, UserPoint::where('user_id', $this->member->id)->value('balance'));
        $this->assertSame('pending', Order::with('state')->find($orderId)?->state?->state);

        $confirmed = OrderState::where('state', 'confirmed')->first();
        $this->actingAs($this->admin, 'sanctum')
            ->putJson("/api/v1/orders/{$orderId}/state", ['state_id' => $confirmed->id])
            ->assertOk()
            ->assertJsonPath('data.status', 'confirmed')
            ->assertJsonPath('data.status_label', 'مؤكد');

        $this->assertSame(10, $this->book->fresh()->amount);
        $this->assertSame(499, UserPoint::where('user_id', $this->member->id)->value('balance'));
        $this->assertNull(Order::find($orderId)?->pickup_expires_at);
    }

    public function test_reject_confirmed_order_requires_reason_and_refunds_sale_stock(): void
    {
        $confirmed = OrderState::where('state', 'confirmed')->first();
        $rejected = OrderState::where('state', 'rejected')->first();
        $order = Order::create([
            'user_id' => $this->member->id,
            'state_id' => $this->pendingState->id,
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
        $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson("/api/v1/orders/{$order->id}/state", ['state_id' => $confirmed->id])
            ->assertStatus(200);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson("/api/v1/orders/{$order->id}/state", ['state_id' => $rejected->id])
            ->assertStatus(422)
            ->assertJsonPath('errors.reason.0', 'سبب الرفض أو الإلغاء مطلوب');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson("/api/v1/orders/{$order->id}/state", [
                'state_id' => $rejected->id,
                'reason' => 'النسخة غير مطابقة للطلب',
            ])
            ->assertOk()
            ->assertJsonPath('data.state.state', 'rejected')
            ->assertJsonPath('data.reason', 'النسخة غير مطابقة للطلب');

        $this->assertSame(10, $this->book->fresh()->amount);
        $this->assertSame(500, UserPoint::where('user_id', $this->member->id)->value('balance'));
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'state_reason' => 'النسخة غير مطابقة للطلب',
        ]);
        $this->assertDatabaseHas('point_transactions', [
            'user_id' => $this->member->id,
            'reference_id' => (string) $order->id,
            'note' => 'استرداد نقاط طلب مرفوض',
        ]);
    }

    public function test_paper_purchase_uses_sale_stock_not_borrow_copies(): void
    {
        $this->book->update(['amount' => 0]);
        $available = InstanceState::create(['state' => 'available']);
        BookInstance::create([
            'book_ISBN' => $this->book->ISBN,
            'state_id' => $available->id,
            'condition' => 'new',
        ]);

        $token = $this->admin->createToken('test')->plainTextToken;
        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/orders', [
                'user_id' => $this->member->id,
                'items' => [['isbn' => $this->book->ISBN, 'count' => 1]],
            ])
            ->assertStatus(422)
            ->assertJsonPath('body', 'الكمية المتاحة من نسخ البيع للكتاب ك غير كافية');

        $this->assertSame(1, BookInstance::where('book_ISBN', $this->book->ISBN)->count());
        $this->assertSame(0, $this->book->fresh()->amount);
    }

    public function test_expire_unclaimed_order_refunds_with_system_reason(): void
    {
        $confirmed = OrderState::where('state', 'confirmed')->first();
        $order = Order::create([
            'user_id' => $this->member->id,
            'state_id' => $this->pendingState->id,
            'total_prices' => 50,
            'total_points' => 1,
            'total_amount' => 1,
        ]);
        OrderItem::create([
            'order_id' => $order->id,
            'book_ISBN' => $this->book->ISBN,
            'price_once' => $this->book->price,
            'count' => 1,
        ]);

        $token = $this->admin->createToken('test')->plainTextToken;
        $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson("/api/v1/orders/{$order->id}/state", ['state_id' => $confirmed->id])
            ->assertOk();

        $order->update(['pickup_expires_at' => now()->subHour()]);

        $this->artisan('orders:expire-unclaimed')->assertSuccessful();

        $expired = $order->fresh('state');
        $this->assertSame('cancelled', $expired->state?->state);
        $this->assertSame('انتهت مهلة الاستلام دون حضور العضو', $expired->state_reason);
        $this->assertSame(10, $this->book->fresh()->amount);
        $this->assertSame(500, UserPoint::where('user_id', $this->member->id)->value('balance'));
    }

    public function test_order_state_notification_uses_arabic_state_name(): void
    {
        Notification::fake();

        $confirmed = OrderState::where('state', 'confirmed')->first();
        $order = Order::create([
            'user_id' => $this->member->id,
            'state_id' => $this->pendingState->id,
            'total_prices' => 50,
            'total_points' => 1,
            'total_amount' => 1,
        ]);
        OrderItem::create([
            'order_id' => $order->id,
            'book_ISBN' => $this->book->ISBN,
            'price_once' => $this->book->price,
            'count' => 1,
        ]);

        $token = $this->admin->createToken('test')->plainTextToken;
        $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson("/api/v1/orders/{$order->id}/state", ['state_id' => $confirmed->id])
            ->assertOk()
            ->assertJsonPath('data.status_label', 'مؤكد')
            ->assertJsonPath('data.state.label', 'مؤكد');

        Notification::assertSentTo($this->member, OrderStateChangedNotification::class, function ($notification) {
            $data = $notification->toArray($this->member);

            return $data['state'] === 'مؤكد'
                && $data['state_key'] === 'confirmed'
                && $data['state_label'] === 'مؤكد'
                && ! str_contains((string) $data['message'], 'confirmed');
        });
    }

    public function test_librarian_can_create_and_confirm_order_like_admin(): void
    {
        $librarian = User::factory()->create(['role' => 'LIBRARIAN']);
        $confirmed = OrderState::where('state', 'confirmed')->first();

        $this->actingAs($librarian)
            ->postJson('/api/v1/orders', [
                'user_id' => $this->member->id,
                'items' => [['isbn' => $this->book->ISBN, 'count' => 1, 'format' => 'paper']],
            ])
            ->assertCreated()
            ->assertJsonPath('data.total_amount', 1);

        $orderId = Order::query()->latest('id')->value('id');

        $this->actingAs($librarian)
            ->putJson("/api/v1/orders/{$orderId}/state", ['state_id' => $confirmed->id])
            ->assertOk()
            ->assertJsonPath('data.state.state', 'confirmed');

        $this->assertSame(9, $this->book->fresh()->amount);
        $this->assertSame(499, UserPoint::where('user_id', $this->member->id)->value('balance'));
    }
}
