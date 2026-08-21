<?php

namespace App\Http\Controllers\App;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Services\AblyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RealtimeController extends Controller
{
    public function __construct(private AblyService $ably) {}

    public function token(Request $request): JsonResponse
    {
        if (! $this->ably->isEnabled()) {
            return ResponseHelper::error('خدمة الإشعارات اللحظية غير مفعّلة', 503);
        }

        try {
            return ResponseHelper::success(
                $this->ably->createUserTokenRequest((int) $request->user()->id),
                'تم إنشاء رمز الاتصال اللحظي'
            );
        } catch (\Throwable $e) {
            report($e);

            return ResponseHelper::error('تعذر إنشاء رمز الاتصال اللحظي', 500);
        }
    }
}
