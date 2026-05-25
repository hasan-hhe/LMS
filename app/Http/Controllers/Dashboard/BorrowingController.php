<?php

namespace App\Http\Controllers\Dashboard;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Borrowing\ExtendBorrowingRequest;
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
            $filters    = $request->only(['member_id', 'is_returned']);
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

    public function returnBook(int $id): JsonResponse
    {
        try {
            $borrowing = $this->borrowingService->returnBook($id);
            return ResponseHelper::success(new BorrowingResource($borrowing), 'تم إعادة الكتاب بنجاح');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage(), 422);
        }
    }

    public function extend(ExtendBorrowingRequest $request, int $id): JsonResponse
    {
        try {
            $borrowing = $this->borrowingService->extendBorrowing($id, $request->validated());
            return ResponseHelper::success(new BorrowingResource($borrowing), 'تم تمديد الاستعارة بنجاح');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage(), 422);
        }
    }
}
