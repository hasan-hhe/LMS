<?php

namespace App\Http\Controllers\Dashboard;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Order\StoreOrderRequest;
use App\Http\Requests\Order\UpdateOrderStateRequest;
use App\Http\Resources\OrderResource;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(private OrderService $orderService) {}

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
            $order = $this->orderService->updateOrderState($id, $request->state_id);
            return ResponseHelper::success(new OrderResource($order), 'تم تحديث حالة الطلب بنجاح');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage(), 422);
        }
    }
}
