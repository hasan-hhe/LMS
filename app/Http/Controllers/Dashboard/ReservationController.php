<?php

namespace App\Http\Controllers\Dashboard;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Reservation\StoreReservationRequest;
use App\Http\Resources\ReservationResource;
use App\Services\ReservationService;
use Illuminate\Http\JsonResponse;

class ReservationController extends Controller
{
    public function __construct(private ReservationService $reservationService) {}

    public function index(): JsonResponse
    {
        try {
            $reservations = $this->reservationService->listReservations();
            return ResponseHelper::paginated(
                ReservationResource::collection($reservations),
                'تم جلب قائمة الحجوزات'
            );
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage(), 500);
        }
    }

    public function store(StoreReservationRequest $request): JsonResponse
    {
        try {
            $reservation = $this->reservationService->createReservation($request->validated());
            return ResponseHelper::created(new ReservationResource($reservation), 'تم تسجيل الحجز بنجاح');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage(), 422);
        }
    }

    public function cancel(int $id): JsonResponse
    {
        try {
            $reservation = $this->reservationService->cancelReservation($id);
            return ResponseHelper::success(new ReservationResource($reservation), 'تم إلغاء الحجز بنجاح');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage(), 422);
        }
    }
}
