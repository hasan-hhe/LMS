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
    public const PICKUP_HOURS = 48;

    public function __construct(private PointService $pointService, private FineService $fineService) {}

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
            $this->fineService->assertMemberHasNoUnpaidFines((int) $data['user_id']);
            $pendingState = $this->findOrFailOrderState('pending');

            [$totalPrice, $totalPoints, $totalAmount] = $this->calculateOrderTotals($data['items']);

            if ($totalPoints > 0 && $this->pointService->getBalance((int) $data['user_id']) < $totalPoints) {
                throw new \Exception('رصيد النقاط غير كافٍ لإتمام العملية');
            }

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

    public function updateOrderState(int $orderId, int $stateId, ?string $reason = null): Order
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
            $this->assertValidTransition($currentState?->state, $state->state);
            $isFirstConfirmation = $state->state === 'confirmed'
                && $currentState?->state !== 'confirmed';
            $isRejectOrCancel = in_array($state->state, ['cancelled', 'rejected'], true);
            $reason = is_string($reason) ? trim($reason) : null;

            if ($isRejectOrCancel) {
                $this->assertReasonRequired($reason);
            }

            if ($isFirstConfirmation) {
                $this->fineService->assertMemberHasNoUnpaidFines((int) $order->user_id);
                $order->loadMissing(['items']);
                $paperRequired = $this->paperSaleQuantities($order);

                $books = Book::whereIn('ISBN', $paperRequired->keys())
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('ISBN');

                foreach ($paperRequired as $isbn => $requiredCount) {
                    $book = $books->get($isbn);
                    if (! $book) {
                        throw new \Exception("الكتاب برقم ISBN {$isbn} غير موجود");
                    }
                    $this->assertSaleStockAvailable($book, (int) $requiredCount);
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

                foreach ($paperRequired as $isbn => $requiredCount) {
                    $books->get($isbn)->decrement('amount', (int) $requiredCount);
                }
            }

            if ($isRejectOrCancel && $currentState?->state === 'confirmed') {
                $this->refundAndRestock($order, $state->state);
            }

            $payload = ['state_id' => $stateId];
            if ($isFirstConfirmation) {
                $hasPaper = $order->items->contains(fn ($item) => ($item->format ?: 'paper') !== 'pdf');
                $payload['pickup_expires_at'] = $hasPaper ? now()->addHours(self::PICKUP_HOURS) : null;
                $payload['state_reason'] = null;
            }
            if ($isRejectOrCancel) {
                $payload['state_reason'] = $reason;
                $payload['pickup_expires_at'] = null;
            }
            if ($state->state === 'delivered') {
                $payload['pickup_expires_at'] = null;
            }

            $order->update($payload);

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

    public function expireUnclaimedOrders(): int
    {
        $cancelled = OrderState::where('state', 'cancelled')->first();
        if (! $cancelled) {
            return 0;
        }

        $orders = Order::query()
            ->whereNotNull('pickup_expires_at')
            ->where('pickup_expires_at', '<', now())
            ->whereHas('state', fn ($q) => $q->where('state', 'confirmed'))
            ->get();

        $count = 0;
        foreach ($orders as $order) {
            try {
                $this->updateOrderState(
                    $order->id,
                    $cancelled->id,
                    'انتهت مهلة الاستلام دون حضور العضو'
                );
                $count++;
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return $count;
    }

    private function refundAndRestock(Order $order, string $targetState): void
    {
        $alreadyRefunded = PointTransaction::where([
            'user_id' => $order->user_id,
            'reference_type' => Order::class,
            'reference_id' => (string) $order->id,
        ])->where('points', '>', 0)->exists();

        if (! $alreadyRefunded && $order->total_points > 0) {
            $note = $targetState === 'rejected'
                ? 'استرداد نقاط طلب مرفوض'
                : 'استرداد نقاط طلب ملغى';
            $this->pointService->credit(
                $order->user_id,
                (int) $order->total_points,
                'adjust',
                Order::class,
                (string) $order->id,
                $note
            );
        }

        $order->loadMissing('items');
        $requiredStock = $this->paperSaleQuantities($order);

        $books = Book::whereIn('ISBN', $requiredStock->keys())
            ->lockForUpdate()
            ->get()
            ->keyBy('ISBN');

        foreach ($requiredStock as $isbn => $count) {
            $book = $books->get($isbn);
            if ($book && $count > 0) {
                $book->increment('amount', $count);
            }
        }
    }

    private function paperSaleQuantities(Order $order)
    {
        return $order->items
            ->filter(fn ($item) => ($item->format ?: 'paper') !== 'pdf')
            ->groupBy('book_ISBN')
            ->map(fn ($items) => (int) $items->sum('count'));
    }

    private function assertReasonRequired(?string $reason): void
    {
        if ($reason === null || $reason === '' || mb_strlen($reason) < 3) {
            throw new \Exception('يجب كتابة سبب الرفض أو الإلغاء');
        }
    }

    private function assertSaleStockAvailable(Book $book, int $count): void
    {
        if ($book->amount < $count) {
            throw new \Exception("الكمية المتاحة من نسخ البيع للكتاب {$book->title} غير كافية");
        }
    }

    private function calculateOrderTotals(array $items): array
    {
        $totalPrice = 0;
        $totalPoints = 0;
        $totalAmount = 0;

        foreach ($items as $item) {
            $book = Book::with('digitalAsset')->find($item['isbn']);
            if (! $book) {
                throw new \Exception("الكتاب برقم ISBN {$item['isbn']} غير موجود");
            }
            $format = $this->normalizeFormat($item['format'] ?? 'paper');
            $this->assertPurchasable($book, $format, (int) $item['count']);
            $totalPrice += $book->price * $item['count'];
            $bookPoints = $book->price_points ?: $this->pointService->sypToPoints((float) $book->price);
            if ($bookPoints < 1) {
                throw new \Exception("سعر النقاط للكتاب {$book->title} غير متوفر");
            }
            $totalPoints += $bookPoints * $item['count'];
            $totalAmount += $item['count'];
        }

        return [$totalPrice, $totalPoints, $totalAmount];
    }

    private function createOrderItems(int $orderId, array $items): void
    {
        foreach ($items as $item) {
            $book = Book::with('digitalAsset')->find($item['isbn']);
            $format = $this->normalizeFormat($item['format'] ?? 'paper');
            OrderItem::create([
                'order_id' => $orderId,
                'book_ISBN' => $item['isbn'],
                'price_once' => $book->price,
                'count' => $item['count'],
                'format' => $format,
            ]);
        }
    }

    private function normalizeFormat(?string $format): string
    {
        return $format === 'pdf' ? 'pdf' : 'paper';
    }

    private function assertPurchasable(Book $book, string $format, int $count): void
    {
        if ($format === 'pdf') {
            if (! $book->digitalAsset?->hasPdf()) {
                throw new \Exception("لا يتوفر ملف PDF للبيع للكتاب {$book->title}");
            }
            if ($book->digitalAsset->is_free) {
                throw new \Exception("ملف PDF للكتاب {$book->title} مجاني ولا يحتاج شراء");
            }

            return;
        }

        $this->assertSaleStockAvailable($book, $count);
    }

    private function assertValidTransition(?string $from, string $to): void
    {
        if ($from === $to) {
            return;
        }

        $allowed = [
            'pending' => ['confirmed', 'cancelled', 'rejected'],
            'confirmed' => ['delivered', 'cancelled', 'rejected'],
            'delivered' => [],
            'cancelled' => [],
            'rejected' => [],
        ];

        if (! in_array($to, $allowed[$from] ?? [], true)) {
            throw new \Exception('لا يمكن تغيير حالة الطلب من '.($from ?: 'غير معروفة').' إلى '.$to);
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
