<?php

namespace App\Http\Controllers\App;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Borrowing\ExtendBorrowingRequest;
use App\Http\Requests\Points\RedeemTopUpCodeRequest;
use App\Http\Resources\BorrowingResource;
use App\Http\Resources\LateFineResource;
use App\Http\Resources\OrderResource;
use App\Http\Resources\PointBalanceResource;
use App\Http\Resources\PointTransactionResource;
use App\Http\Resources\ReservationResource;
use App\Models\BookInstance;
use App\Models\Borrowing;
use App\Models\LateFine;
use App\Models\Order;
use App\Models\OrderState;
use App\Models\Reservation;
use App\Services\BorrowingService;
use App\Services\FineService;
use App\Services\MembershipService;
use App\Services\OrderService;
use App\Services\PointService;
use App\Services\ReservationService;
use App\Services\TopUpCodeService;
use App\Support\MemberStatusLabels;
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
        private MembershipService $membershipService,
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
        $query = Borrowing::with(['bookInstance.book.author', 'lateFine', 'editions'])
            ->where('member_id', $request->user()->id);
        $status = $request->input('status');
        if (in_array($status, ['active', 'current'], true)) {
            $query->whereNull('returned_at');
        } elseif (in_array($status, ['returned', 'completed'], true)) {
            $query->whereNotNull('returned_at');
        }

        $borrowings = $query->orderByDesc('id')->paginate(15);

        return ResponseHelper::success([
            'items' => BorrowingResource::collection($borrowings->items()),
            'data' => BorrowingResource::collection($borrowings->items()),
            'meta' => $this->paginationMeta($borrowings),
        ], 'تم جلب الاستعارات بنجاح');
    }

    public function quoteExtension(Request $request, int $id)
    {
        if (! Borrowing::whereKey($id)->where('member_id', $request->user()->id)->exists()) {
            return ResponseHelper::notFound('الاستعارة غير موجودة');
        }

        try {
            return ResponseHelper::success(
                $this->borrowingService->quoteExtension($id, $request->input('new_end_date')),
                'تم حساب تكلفة التمديد'
            );
        } catch (\Throwable $e) {
            return ResponseHelper::error($e->getMessage(), 422);
        }
    }

    public function membership(Request $request)
    {
        return ResponseHelper::success(
            $this->membershipService->status($request->user()),
            'تم جلب حالة العضوية'
        );
    }

    public function subscribeMembership(Request $request)
    {
        try {
            return ResponseHelper::success(
                $this->membershipService->subscribe($request->user()),
                'تم تفعيل أو تمديد العضوية بنجاح'
            );
        } catch (\Throwable $e) {
            return ResponseHelper::error($e->getMessage(), 422);
        }
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
            $fine = $this->fineService->payFine($id);
            $message = $fine->is_paid
                ? 'تم دفع الغرامة بالنقاط بنجاح'
                : 'تم خصم المتوفر من رصيدك، ويتبقى جزء من الغرامة حتى الشحن أو الدفع نقداً في المكتبة';

            return ResponseHelper::success(new LateFineResource($fine), $message);
        } catch (\Throwable $e) {
            return ResponseHelper::error($e->getMessage(), 422);
        }
    }

    public function reservations(Request $request)
    {
        $reservations = Reservation::with(['bookInstance.book.author', 'state'])
            ->where('user_id', $request->user()->id)
            ->orderByDesc('id')
            ->paginate(15);

        return ResponseHelper::success([
            'items' => ReservationResource::collection($reservations->items())->resolve(),
            'data' => ReservationResource::collection($reservations->items())->resolve(),
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
                ->whereHas('state', fn ($query) => $query->where('state', 'available'))
                ->first();
            if (! $instance) {
                return ResponseHelper::error('لا توجد نسخة متاحة للحجز لهذا الكتاب', 422);
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
        $filters = ['user_id' => $request->user()->id];
        $status = $request->input('status');
        if (is_string($status) && $status !== '') {
            $state = OrderState::where('state', $status)->first();
            if ($state) {
                $filters['state_id'] = $state->id;
            }
        }
        $orders = $this->orderService->listOrders($filters);

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
            'items.*.format' => ['nullable', 'in:paper,pdf'],
        ], [
            'items.required' => 'عناصر الطلب مطلوبة',
            'items.min' => 'يجب أن يحتوي الطلب على عنصر واحد على الأقل',
            'items.*.isbn.required' => 'رقم ISBN للكتاب مطلوب',
            'items.*.isbn.exists' => 'أحد الكتب المحددة غير موجود',
            'items.*.count.required' => 'الكمية مطلوبة',
            'items.*.count.min' => 'الكمية يجب أن تكون واحداً على الأقل',
            'items.*.format.in' => 'نوع الكتاب يجب أن يكون ورقياً أو PDF',
        ]);
        $data['user_id'] = $request->user()->id;

        try {
            return ResponseHelper::created(new OrderResource($this->orderService->createOrder($data)), 'تم إرسال الطلب وهو قيد المراجعة. سيُخصم الرصيد عند تأكيد أمين المكتبة');
        } catch (\Throwable $e) {
            return ResponseHelper::error($e->getMessage(), 422);
        }
    }

    public function payOrder(Request $request, int $id)
    {
        $order = Order::with(['user', 'state', 'items.book'])
            ->whereKey($id)
            ->where('user_id', $request->user()->id)
            ->first();
        if (! $order) {
            return ResponseHelper::notFound('الطلب غير موجود');
        }

        return ResponseHelper::success(
            new OrderResource($order),
            'الطلب قيد المراجعة بانتظار تأكيد أمين المكتبة، وسيصلك إشعار عند التأكيد أو الرفض'
        );
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
        $notifications = $request->user()->notifications()->paginate(20);
        $notifications->setCollection(
            $notifications->getCollection()->map(function ($notification) {
                $item = $notification->toArray();
                if (isset($item['data']) && is_array($item['data'])) {
                    $item['data'] = MemberStatusLabels::localizePayload($item['data']);
                }

                return $item;
            })
        );

        return ResponseHelper::success($notifications, 'تم جلب الإشعارات بنجاح');
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
