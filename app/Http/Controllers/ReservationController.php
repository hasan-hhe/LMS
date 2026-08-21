<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Http\Requests\Reservation\StoreReservationRequest;
use App\Http\Resources\ReservationResource;
use App\Models\Reservation;
use App\Services\ReservationService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReservationController extends Controller
{
    public function __construct(private ReservationService $reservationService) {}

    public function index()
    {
        $user = Auth::user();
        if (hash_equals($user->role, 'MEMBER')) {
           $reservations = Reservation::where('user_id', $user->id)
           ->with('state', 'bookInstance')->paginate(15);
           return ResponseHelper::paginated(ReservationResource::collection($reservations));
           }
           $reservations = Reservation::with('state', 'user', 'bookInstance')->paginate(15);
           return ResponseHelper::paginated(ReservationResource::collection($reservations));
    }

    public function store(StoreReservationRequest $request)
    {
        $user = $request->user();
        if (!hash_equals($user->role, 'MEMBER'))
            return ResponseHelper::unauthorized();
        try {
            $reservation = $this->reservationService->createReservation($request->validated());
            return ResponseHelper::created(new ReservationResource($reservation), 'تم تسجيل الحجز بنجاح');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage(), 422);
        }
    }
}
