<?php

namespace App\Http\Controllers\Dashboard;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Notification\SendNotificationRequest;
use App\Http\Resources\DashboardNotificationResource;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function __construct(private NotificationService $notifications) {}

    public function index(Request $request): JsonResponse
    {
        try {
            $notifications = $this->notifications->list($request->only(['search', 'per_page']));

            return ResponseHelper::paginated(
                DashboardNotificationResource::collection($notifications),
                'تم جلب قائمة الإشعارات'
            );
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage(), 500);
        }
    }

    public function send(SendNotificationRequest $request): JsonResponse
    {
        try {
            $result = $this->notifications->send($request->validated(), (int) $request->user()->id);

            return ResponseHelper::created($result, 'تم إرسال الإشعار إلى '.$result['sent_count'].' مستخدم');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage(), 422);
        }
    }
}
