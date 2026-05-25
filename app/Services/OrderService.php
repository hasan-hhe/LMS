<?php

namespace App\Services;

use App\Models\Book;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderState;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function listOrders(array $filters): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        try {
            $query = Order::with(['user', 'state', 'items.book']);

            if (!empty($filters['user_id'])) {
                $query->where('user_id', $filters['user_id']);
            }

            if (!empty($filters['state_id'])) {
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
            if (!$order) {
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

            [$totalPrice, $totalAmount] = $this->calculateOrderTotals($data['items']);

            $order = Order::create([
                'user_id'      => $data['user_id'],
                'state_id'     => $pendingState->id,
                'total_prices' => $totalPrice,
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
            $order = Order::find($orderId);
            if (!$order) {
                throw new \Exception('الطلب غير موجود');
            }

            $order->update(['state_id' => $stateId]);

            DB::commit();
            return $order->fresh(['user', 'state', 'items.book']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    private function calculateOrderTotals(array $items): array
    {
        $totalPrice  = 0;
        $totalAmount = 0;

        foreach ($items as $item) {
            $book = Book::find($item['isbn']);
            if (!$book) {
                throw new \Exception("الكتاب برقم ISBN {$item['isbn']} غير موجود");
            }
            $totalPrice  += $book->price * $item['count'];
            $totalAmount += $item['count'];
        }

        return [$totalPrice, $totalAmount];
    }

    private function createOrderItems(int $orderId, array $items): void
    {
        foreach ($items as $item) {
            $book = Book::find($item['isbn']);
            OrderItem::create([
                'order_id'   => $orderId,
                'book_ISBN'  => $item['isbn'],
                'price_once' => $book->price,
                'count'      => $item['count'],
            ]);
        }
    }

    private function findOrFailOrderState(string $stateName): OrderState
    {
        $state = OrderState::where('state', $stateName)->first();
        if (!$state) {
            throw new \Exception("حالة الطلب '{$stateName}' غير موجودة في قاعدة البيانات");
        }
        return $state;
    }
}
