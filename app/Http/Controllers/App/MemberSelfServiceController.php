<?php

namespace App\Http\Controllers\App;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Borrowing\ExtendBorrowingRequest;
use App\Http\Requests\Points\RedeemTopUpCodeRequest;
use App\Http\Resources\LateFineResource;
use App\Http\Resources\OrderResource;
use App\Http\Resources\PointBalanceResource;
use App\Http\Resources\PointTransactionResource;
use App\Models\BookInstance;
use App\Models\Borrowing;
use App\Models\LateFine;
use App\Models\Order;
use App\Models\OrderState;
use App\Models\Reservation;
use App\Services\BorrowingService;
use App\Services\FineService;
use App\Services\OrderService;
use App\Services\PointService;
use App\Services\ReservationService;
use App\Services\TopUpCodeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MemberSelfServiceController extends Controller
{
    public function __construct(
        private PointService $pointService,
        private TopUpCodeService $topUpCodeService,
        private FineService $fineService,
        private ReservationService $reservationService,
        private OrderService $orderService,
        private BorrowingService $borrowingService,
    ) {}

    public function balance(Request $request)
    {
        return ResponseHelper::success(
            new PointBalanceResource($this->pointService->getOrCreateBalance($request->user()->id)),
            'تم جلب رصيد النقاط بنجاح'
        );
    }

    public function pointHistory(Request $request)
    {
        $history = $this->pointService->getHistory($request->user()->id, min((int) $request->input('per_page', 15), 50));

        return ResponseHelper::success([
            'items' => PointTransactionResource::collection($history->items()),
            'meta' => $this->paginationMeta($history),
        ], 'تم جلب سجل النقاط بنجاح');
    }

    public function topUp(RedeemTopUpCodeRequest $request)
    {
        try {
            $code = $this->topUpCodeService->redeem($request->validated('code'), $request->user()->id);

            return ResponseHelper::success([
                'points_added' => $code->points_value,
                'balance' => $this->pointService->getBalance($request->user()->id),
            ], 'تم شحن رصيد النقاط بنجاح');
        } catch (\Throwable $e) {
            return ResponseHelper::error($e->getMessage(), 422);
        }
    }

    public function borrowings(Request $request)
    {
        $query = Borrowing::with(['bookInstance.book', 'lateFine', 'editions'])
            ->where('member_id', $request->user()->id);
        $status = $request->input('status');
        if (in_array($status, ['active', 'current'], true)) {
            $query->whereNull('returned_at');
        } elseif (in_array($status, ['returned', 'completed'], true)) {
            $query->whereNotNull('returned_at');
        }

        $borrowings = $query->orderByDesc('id')->paginate(15);

        return ResponseHelper::success([
            'items' => $borrowings->items(),
            'data' => $borrowings->items(),
            'meta' => $this->paginationMeta($borrowings),
        ], 'تم جلب الاستعارات بنجاح');
    }

    public function extend(ExtendBorrowingRequest $request, int $id)
    {
        if (! Borrowing::whereKey($id)->where('member_id', $request->user()->id)->exists()) {
            return ResponseHelper::notFound('الاستعارة غير موجودة');
        }

        try {
            return ResponseHelper::success(
                $this->borrowingService->extendBorrowing($id, $request->validated()),
                'تم تمديد الاستعارة بنجاح'
            );
        } catch (\Throwable $e) {
            return ResponseHelper::error($e->getMessage(), 422);
        }
    }

    public function fines(Request $request)
    {
        $fines = LateFine::with('borrowing.bookInstance.book')
            ->whereHas('borrowing', fn ($query) => $query->where('member_id', $request->user()->id))
            ->orderByDesc('id')->paginate(15);

        return ResponseHelper::success([
            'items' => LateFineResource::collection($fines->items()),
            'meta' => $this->paginationMeta($fines),
        ], 'تم جلب الغرامات بنجاح');
    }

    public function payFine(Request $request, int $id)
    {
        if (! LateFine::whereKey($id)->whereHas('borrowing', fn ($q) => $q->where('member_id', $request->user()->id))->exists()) {
            return ResponseHelper::notFound('الغرامة غير موجودة');
        }

        try {
            return ResponseHelper::success(new LateFineResource($this->fineService->payFine($id)), 'تم دفع الغرامة بالنقاط بنجاح');
        } catch (\Throwable $e) {
            return ResponseHelper::error($e->getMessage(), 422);
        }
    }

    public function reservations(Request $request)
    {
        $reservations = Reservation::with(['bookInstance.book', 'state'])
            ->where('user_id', $request->user()->id)
            ->orderByDesc('id')
            ->paginate(15);

        return ResponseHelper::success([
            'items' => $reservations->items(),
            'data' => $reservations->items(),
            'meta' => $this->paginationMeta($reservations),
        ], 'تم جلب الحجوزات بنجاح');
    }

    public function createReservation(Request $request)
    {
        $data = $request->validate([
            'book_instance_id' => ['nullable', 'integer', 'exists:book_instances,id', 'required_without:isbn'],
            'isbn' => ['nullable', 'string', 'exists:books,ISBN', 'required_without:book_instance_id'],
            'cause' => ['nullable', 'string', 'max:255'],
        ], [
            'book_instance_id.required_without' => 'معرف نسخة الكتاب أو رقم ISBN مطلوب',
            'book_instance_id.exists' => 'نسخة الكتاب غير موجودة',
            'isbn.required_without' => 'رقم ISBN أو معرف نسخة الكتاب مطلوب',
            'isbn.exists' => 'الكتاب غير موجود',
        ]);
        if (empty($data['book_instance_id'])) {
            $instance = BookInstance::where('book_ISBN', $data['isbn'])
                ->orderByRaw("CASE WHEN state_id IN (SELECT id FROM instance_states WHERE state = 'available') THEN 0 ELSE 1 END")
                ->first();
            if (! $instance) {
                return ResponseHelper::notFound('لا توجد نسخة لهذا الكتاب');
            }
            $data['book_instance_id'] = $instance->id;
        }
        $data['user_id'] = $request->user()->id;

        try {
            return ResponseHelper::created($this->reservationService->createReservation($data), 'تم إنشاء الحجز بنجاح');
        } catch (\Throwable $e) {
            return ResponseHelper::error($e->getMessage(), 422);
        }
    }

    public function cancelReservation(Request $request, int $id)
    {
        if (! Reservation::whereKey($id)->where('user_id', $request->user()->id)->exists()) {
            return ResponseHelper::notFound('الحجز غير موجود');
        }
        try {
            return ResponseHelper::success($this->reservationService->cancelReservation($id), 'تم إلغاء الحجز بنجاح');
        } catch (\Throwable $e) {
            return ResponseHelper::error($e->getMessage(), 422);
        }
    }

    public function orders(Request $request)
    {
        $orders = $this->orderService->listOrders(['user_id' => $request->user()->id]);

        return ResponseHelper::success([
            'items' => OrderResource::collection($orders->items()),
            'meta' => $this->paginationMeta($orders),
        ], 'تم جلب الطلبات بنجاح');
    }

    public function showOrder(Request $request, int $id)
    {
        $order = Order::with(['user', 'state', 'items.book'])->whereKey($id)->where('user_id', $request->user()->id)->first();

        return $order
            ? ResponseHelper::success(new OrderResource($order), 'تم جلب الطلب بنجاح')
            : ResponseHelper::notFound('الطلب غير موجود');
    }

    public function createOrder(Request $request)
    {
        $data = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.isbn' => ['required', 'string', 'exists:books,ISBN'],
            'items.*.count' => ['required', 'integer', 'min:1'],
        ], [
            'items.required' => 'عناصر الطلب مطلوبة',
            'items.min' => 'يجب أن يحتوي الطلب على عنصر واحد على الأقل',
            'items.*.isbn.required' => 'رقم ISBN للكتاب مطلوب',
            'items.*.isbn.exists' => 'أحد الكتب المحددة غير موجود',
            'items.*.count.required' => 'الكمية مطلوبة',
            'items.*.count.min' => 'الكمية يجب أن تكون واحداً على الأقل',
        ]);
        $data['user_id'] = $request->user()->id;

        try {
            return ResponseHelper::created(new OrderResource($this->orderService->createOrder($data)), 'تم إنشاء الطلب بنجاح');
        } catch (\Throwable $e) {
            return ResponseHelper::error($e->getMessage(), 422);
        }
    }

    public function payOrder(Request $request, int $id)
    {
        $order = Order::whereKey($id)->where('user_id', $request->user()->id)->first();
        if (! $order) {
            return ResponseHelper::notFound('الطلب غير موجود');
        }
        if ($order->state?->state !== 'pending') {
            return ResponseHelper::error('لا يمكن دفع الطلب في حالته الحالية', 422);
        }
        $confirmed = OrderState::where('state', 'confirmed')->first();
        if (! $confirmed) {
            return ResponseHelper::error('حالة الطلب المؤكد غير موجودة', 500);
        }
        try {
            return ResponseHelper::success(new OrderResource($this->orderService->updateOrderState($id, $confirmed->id)), 'تم تأكيد الطلب ودفعه بالنقاط');
        } catch (\Throwable $e) {
            return ResponseHelper::error($e->getMessage(), 422);
        }
    }

    public function updateProfile(Request $request)
    {
        $data = $request->validate([
            'first_name' => ['sometimes', 'string', 'max:100'],
            'last_name' => ['sometimes', 'string', 'max:100'],
            'phone' => ['sometimes', 'string', 'regex:/^[0-9]+$/', 'unique:users,phone,'.$request->user()->id],
            'adress' => ['sometimes', 'nullable', 'string', 'max:255'],
            'photo' => ['sometimes', 'nullable', 'image', 'max:2048'],
        ], [
            'phone.regex' => 'رقم الهاتف يجب أن يحتوي على أرقام فقط',
            'phone.unique' => 'رقم الهاتف مستخدم مسبقاً',
            'photo.image' => 'الملف المرفق يجب أن يكون صورة',
            'photo.max' => 'حجم الصورة لا يتجاوز 2 ميغابايت',
        ]);
        if ($request->hasFile('photo')) {
            if ($request->user()->photo_url) {
                Storage::disk('public')->delete($request->user()->photo_url);
            }
            $data['photo_url'] = $request->file('photo')->store('profiles', 'public');
        }
        unset($data['photo']);
        $request->user()->update($data);

        return ResponseHelper::success($request->user()->fresh(), 'تم تحديث الملف الشخصي بنجاح');
    }

    public function notifications(Request $request)
    {
        return ResponseHelper::success($request->user()->notifications()->paginate(20), 'تم جلب الإشعارات بنجاح');
    }

    public function readNotification(Request $request, string $id)
    {
        $notification = $request->user()->notifications()->find($id);
        if (! $notification) {
            return ResponseHelper::notFound('الإشعار غير موجود');
        }
        $notification->markAsRead();

        return ResponseHelper::success(null, 'تم تعليم الإشعار كمقروء');
    }

    public function readAllNotifications(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();

        return ResponseHelper::success(null, 'تم تعليم جميع الإشعارات كمقروءة');
    }

    private function paginationMeta($paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
        ];
    }
}
