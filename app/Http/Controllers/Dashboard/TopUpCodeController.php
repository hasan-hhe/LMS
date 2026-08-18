<?php

namespace App\Http\Controllers\Dashboard;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Points\GenerateTopUpCodesRequest;
use App\Http\Requests\Points\RedeemTopUpCodeRequest;
use App\Http\Resources\TopUpCodeResource;
use App\Models\User;
use App\Services\TopUpCodeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TopUpCodeController extends Controller
{
    public function __construct(private TopUpCodeService $service) {}

    public function index(Request $request): JsonResponse
    {
        $codes = $this->service->list($request->only(['is_used', 'user_id', 'per_page']));

        return ResponseHelper::paginated(TopUpCodeResource::collection($codes), 'تم جلب رموز شحن النقاط');
    }

    public function generate(GenerateTopUpCodesRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            $codes = $this->service->generateBatch(
                (int) $data['count'], (int) $data['points_value'], $data['expires_at'] ?? null,
                isset($data['user_id']) ? (int) $data['user_id'] : null, (int) $request->user()->id
            );

            return ResponseHelper::created(TopUpCodeResource::collection(collect($codes)), 'تم إنشاء رموز شحن النقاط');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage(), 422);
        }
    }

    public function redeem(RedeemTopUpCodeRequest $request): JsonResponse
    {
        try {
            $memberId = (int) ($request->input('member_id') ?: $request->user()->id);
            if (! User::where('id', $memberId)->where('role', 'MEMBER')->exists()) {
                throw new \Exception('العضو غير موجود');
            }

            return ResponseHelper::success(new TopUpCodeResource($this->service->redeem($request->code, $memberId)), 'تم شحن رصيد النقاط بنجاح');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage(), 422);
        }
    }
}
