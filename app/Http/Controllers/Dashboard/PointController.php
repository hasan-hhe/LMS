<?php

namespace App\Http\Controllers\Dashboard;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Points\AdjustPointsRequest;
use App\Http\Requests\Points\UpdatePointSettingsRequest;
use App\Http\Resources\PointBalanceResource;
use App\Http\Resources\PointTransactionResource;
use App\Models\User;
use App\Services\FineService;
use App\Services\PointService;
use App\Services\PointSettingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PointController extends Controller
{
    public function __construct(
        private PointService $points,
        private PointSettingService $settings,
        private FineService $fineService,
    ) {}

    public function balance(Request $request): JsonResponse
    {
        try {
            $userId = $this->memberId($request);

            return ResponseHelper::success(new PointBalanceResource($this->points->getOrCreateBalance($userId)), 'تم جلب رصيد النقاط');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage(), 422);
        }
    }

    public function history(Request $request): JsonResponse
    {
        try {
            $history = $this->points->getHistory($this->memberId($request), min(100, max(1, (int) $request->input('per_page', 15))));

            return ResponseHelper::paginated(PointTransactionResource::collection($history), 'تم جلب سجل النقاط');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage(), 422);
        }
    }

    public function adjust(AdjustPointsRequest $request): JsonResponse
    {
        try {
            if (! User::where('id', $request->member_id)->where('role', 'MEMBER')->exists()) {
                throw new \Exception('العضو غير موجود');
            }
            $transaction = $this->points->adjust((int) $request->member_id, (int) $request->points, $request->note, (int) $request->user()->id);
            if ((int) $request->points > 0) {
                $this->fineService->settleUnpaidFinesFromBalance((int) $request->member_id);
            }

            return ResponseHelper::success(new PointTransactionResource($transaction), 'تم تعديل رصيد النقاط');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage(), 422);
        }
    }

    public function settings(): JsonResponse
    {
        return ResponseHelper::success($this->settings->getAll()->pluck('value', 'key'), 'تم جلب إعدادات النقاط');
    }

    public function updateSettings(UpdatePointSettingsRequest $request): JsonResponse
    {
        try {
            foreach ($request->validated() as $key => $value) {
                $this->settings->update($key, $value);
            }

            return ResponseHelper::success($this->settings->getAll()->pluck('value', 'key'), 'تم تحديث الإعدادات العامة');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage(), 422);
        }
    }

    private function memberId(Request $request): int
    {
        $id = (int) ($request->input('member_id') ?: $request->user()->id);
        if (! User::where('id', $id)->where('role', 'MEMBER')->exists()) {
            throw new \Exception('العضو غير موجود');
        }

        return $id;
    }
}
