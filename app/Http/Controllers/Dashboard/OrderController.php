<?php

namespace App\Http\Controllers\Dashboard;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Order\StoreOrderRequest;
use App\Http\Requests\Order\UpdateOrderStateRequest;
use App\Http\Resources\OrderResource;
use App\Models\OrderState;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(private OrderService $orderService) {}

    public function states(): JsonResponse
    {
        return ResponseHelper::success(
            OrderState::query()->orderBy('id')->get(['id', 'state']),
            'تم جلب حالات الطلب'
        );
    }

    public function index(Request $request): JsonResponse
    {
        try {
            $filters = $request->only(['user_id', 'state_id']);
            $orders  = $this->orderService->listOrders($filters);
            return ResponseHelper::paginated(OrderResource::collection($orders), 'تم جلب قائمة الطلبات');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage(), 500);
        }
    }

    public function store(StoreOrderRequest $request): JsonResponse
    {
        try {
            $order = $this->orderService->createOrder($request->validated());
            return ResponseHelper::created(new OrderResource($order), 'تم إنشاء الطلب بنجاح');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage(), 422);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $order = $this->orderService->getOrder($id);
            return ResponseHelper::success(new OrderResource($order), 'تم جلب بيانات الطلب');
        } catch (\Exception $e) {
            return ResponseHelper::notFound($e->getMessage());
        }
    }

    public function updateState(UpdateOrderStateRequest $request, int $id): JsonResponse
    {
        try {
            $order = $this->orderService->updateOrderState(
                $id,
                (int) $request->state_id,
                $request->input('reason')
            );
            $message = match ($order->state?->state) {
                'confirmed' => 'تم تأكيد الطلب وخصم النقاط من مخزون البيع. بانتظار استلام العضو من المكتبة',
                'delivered' => 'تم تسليم الطلب للعضو',
                'cancelled' => 'تم إلغاء الطلب وإعادة النقاط ومخزون البيع إن وُجد خصم',
                'rejected' => 'تم رفض الطلب وإعادة النقاط ومخزون البيع إن وُجد خصم',
                default => 'تم تحديث حالة الطلب بنجاح',
            };

            return ResponseHelper::success(new OrderResource($order), $message);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage(), 422);
        }
    }
}
