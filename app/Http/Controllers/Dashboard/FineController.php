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
            $filters = $request->only(['is_paid', 'member_id']);
            $fines   = $this->fineService->listFines($filters);

            return ResponseHelper::paginated(
                LateFineResource::collection($fines),
                'تم جلب قائمة الغرامات'
            );
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage(), 500);
        }
    }

    public function pay(int $id): JsonResponse
    {
        try {
            $fine = $this->fineService->payFine($id);
            return ResponseHelper::success(new LateFineResource($fine), 'تم تسجيل دفع الغرامة بنجاح');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage(), 422);
        }
    }
}
