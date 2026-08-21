<?php

namespace App\Http\Controllers\Dashboard;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Resources\LateFineResource;
use App\Services\FineService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FineController extends Controller
{
    public function __construct(private FineService $fineService) {}

    public function index(Request $request): JsonResponse
    {
        try {
            $filters = $request->only(['is_paid', 'member_id', 'per_page']);
            $fines   = $this->fineService->listFines($filters);

            return ResponseHelper::paginated(
                LateFineResource::collection($fines),
                'تم جلب قائمة الغرامات'
            );
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage(), 500);
        }
    }

    public function pay(Request $request, int $id): JsonResponse
    {
        try {
            $method = $request->input('payment_method', 'points');
            $fine = $this->fineService->payFine($id, is_string($method) ? $method : 'points');
            $message = $method === 'cash'
                ? 'تم تسجيل تحصيل الغرامة نقداً بالليرة السورية'
                : ($fine->is_paid
                    ? 'تم تسجيل دفع الغرامة بالنقاط بنجاح'
                    : 'تم خصم المتوفر من النقاط، ويتبقى على العضو غرامة غير مسددة');

            return ResponseHelper::success(new LateFineResource($fine), $message);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage(), 422);
        }
    }
}
