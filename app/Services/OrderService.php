<?php

namespace App\Services;

use App\Models\Book;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderState;
use App\Models\PointTransaction;
use App\Notifications\OrderStateChangedNotification;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function __construct(private PointService $pointService) {}

    public function listOrders(array $filters): LengthAwarePaginator
    {
        try {
            $query = Order::with(['user', 'state', 'items.book']);

            if (! empty($filters['user_id'])) {
                $query->where('user_id', $filters['user_id']);
            }

            if (! empty($filters['state_id'])) {
                $query->where('state_id', $filters['state_id']);
            }

            return $query->orderByDesc('id')->paginate(15);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function getOrder(int $id): Order
    {
        try {
            $order = Order::with(['user', 'state', 'items.book'])->find($id);
            if (! $order) {
                throw new \Exception('الطلب غير موجود');
            }

            return $order;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function createOrder(array $data): Order
    {
        DB::beginTransaction();
        try {
            $pendingState = $this->findOrFailOrderState('pending');

            [$totalPrice, $totalPoints, $totalAmount] = $this->calculateOrderTotals($data['items']);

            $order = Order::create([
                'user_id' => $data['user_id'],
                'state_id' => $pendingState->id,
                'total_prices' => $totalPrice,
                'total_points' => $totalPoints,
                'total_amount' => $totalAmount,
            ]);

            $this->createOrderItems($order->id, $data['items']);

            DB::commit();

            return $order->load(['user', 'state', 'items.book']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function updateOrderState(int $orderId, int $stateId): Order
    {
        DB::beginTransaction();
        try {
            $order = Order::lockForUpdate()->find($orderId);
            if (! $order) {
                throw new \Exception('الطلب غير موجود');
            }

            $state = OrderState::find($stateId);
            if (! $state) {
                throw new \Exception('حالة الطلب غير موجودة');
            }

            $currentState = OrderState::find($order->state_id);
            $isFirstConfirmation = $state->state === 'confirmed'
                && $currentState?->state !== 'confirmed';

            if ($isFirstConfirmation) {
                $requiredStock = $order->items()
                    ->select('book_ISBN', DB::raw('SUM(count) as required_count'))
                    ->groupBy('book_ISBN')
                    ->pluck('required_count', 'book_ISBN');

                $books = Book::whereIn('ISBN', $requiredStock->keys())
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('ISBN');

                foreach ($requiredStock as $isbn => $requiredCount) {
                    $book = $books->get($isbn);
                    if (! $book) {
                        throw new \Exception("الكتاب برقم ISBN {$isbn} غير موجود");
                    }
                    if ($book->amount < $requiredCount) {
                        throw new \Exception("الكمية المتاحة من الكتاب {$book->title} غير كافية");
                    }
                }

                $hasPayment = PointTransaction::where([
                    'user_id' => $order->user_id,
                    'type' => 'spend',
                    'reference_type' => Order::class,
                    'reference_id' => (string) $order->id,
                ])->exists();

                if (! $hasPayment && $order->total_points > 0) {
                    $this->pointService->debit($order->user_id, $order->total_points, 'spend', Order::class, (string) $order->id, 'دفع قيمة الطلب');
                }

                foreach ($requiredStock as $isbn => $requiredCount) {
                    $books->get($isbn)->decrement('amount', (int) $requiredCount);
                }
            }

            $order->update(['state_id' => $stateId]);

            DB::commit();

            $order = $order->fresh(['user', 'state', 'items.book']);
            try {
                $order->user?->notify(new OrderStateChangedNotification($order));
            } catch (\Throwable $notificationError) {
                report($notificationError);
            }

            return $order;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    private function calculateOrderTotals(array $items): array
    {
        $totalPrice = 0;
        $totalPoints = 0;
        $totalAmount = 0;

        foreach ($items as $item) {
            $book = Book::find($item['isbn']);
            if (! $book) {
                throw new \Exception("الكتاب برقم ISBN {$item['isbn']} غير موجود");
            }
            $totalPrice += $book->price * $item['count'];
            $bookPoints = $book->price_points ?: $this->pointService->sypToPoints((float) $book->price);
            $totalPoints += $bookPoints * $item['count'];
            $totalAmount += $item['count'];
        }

        return [$totalPrice, $totalPoints, $totalAmount];
    }

    private function createOrderItems(int $orderId, array $items): void
    {
        foreach ($items as $item) {
            $book = Book::find($item['isbn']);
            OrderItem::create([
                'order_id' => $orderId,
                'book_ISBN' => $item['isbn'],
                'price_once' => $book->price,
                'count' => $item['count'],
            ]);
        }
    }

    private function findOrFailOrderState(string $stateName): OrderState
    {
        $state = OrderState::where('state', $stateName)->first();
        if (! $state) {
            throw new \Exception("حالة الطلب '{$stateName}' غير موجودة في قاعدة البيانات");
        }

        return $state;
    }
}
