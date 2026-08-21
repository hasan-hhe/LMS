<?php

namespace App\Http\Controllers\Dashboard;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Borrowing\ExtendBorrowingRequest;
use App\Http\Requests\Borrowing\ReturnBorrowingRequest;
use App\Http\Requests\Borrowing\StoreBorrowingRequest;
use App\Http\Resources\BorrowingResource;
use App\Repositories\Interfaces\BorrowingRepositoryInterface;
use App\Services\BorrowingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BorrowingController extends Controller
{
    public function __construct(
        private BorrowingService $borrowingService,
        private BorrowingRepositoryInterface $borrowingRepository
    ) {}

    public function index(Request $request): JsonResponse
    {
        try {
            $filters    = $request->only(['member_id', 'is_returned', 'book_instance_id']);
            $borrowings = $this->borrowingRepository->getAllPaginated($filters);

            return ResponseHelper::paginated(
                BorrowingResource::collection($borrowings),
                'تم جلب قائمة الاستعارات'
            );
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage(), 500);
        }
    }

    public function store(StoreBorrowingRequest $request): JsonResponse
    {
        try {
            $borrowing = $this->borrowingService->checkoutBook(
                $request->validated(),
                $request->user()->id
            );

            return ResponseHelper::created(new BorrowingResource($borrowing), 'تم تسجيل الاستعارة بنجاح');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage(), 422);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $borrowing = $this->borrowingRepository->findById($id);
            if (!$borrowing) {
                return ResponseHelper::notFound('الاستعارة غير موجودة');
            }
            return ResponseHelper::success(new BorrowingResource($borrowing), 'تم جلب بيانات الاستعارة');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage(), 500);
        }
    }

    public function returnBook(ReturnBorrowingRequest $request, int $id): JsonResponse
    {
        try {
            $borrowing = $this->borrowingService->returnBook($id, $request->validated());
            $outcome = $request->input('outcome', 'ok');
            $message = match ($outcome) {
                'damaged' => 'تم تسجيل الإرجاع مع غرامة إتلاف الكتاب',
                'lost' => 'تم تسجيل الفقد مع غرامة بدل الكتاب',
                default => 'تم إعادة الكتاب بنجاح',
            };

            return ResponseHelper::success(new BorrowingResource($borrowing), $message);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage(), 422);
        }
    }

    public function extend(ExtendBorrowingRequest $request, int $id): JsonResponse
    {
        try {
            $borrowing = $this->borrowingService->extendBorrowing(
                $id,
                $request->validated(),
                $request->boolean('administrative')
            );
            return ResponseHelper::success(
                new BorrowingResource($borrowing),
                $request->boolean('administrative') ? 'تم التمديد الإداري بنجاح' : 'تم تمديد الاستعارة بنجاح'
            );
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage(), 422);
        }
    }

    public function quoteExtension(Request $request, int $id): JsonResponse
    {
        try {
            return ResponseHelper::success(
                $this->borrowingService->quoteExtension($id, $request->input('new_end_date')),
                'تم حساب تكلفة التمديد'
            );
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage(), 422);
        }
    }
}
