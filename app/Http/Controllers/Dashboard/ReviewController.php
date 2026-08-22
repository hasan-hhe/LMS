<?php

namespace App\Http\Controllers\Dashboard;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Resources\ReviewResource;
use App\Services\ReviewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function __construct(private ReviewService $reviews) {}

    public function index(Request $request): JsonResponse
    {
        try {
            $paginator = $this->reviews->paginate($request->only(['isbn', 'member_id', 'search', 'per_page']));

            return ResponseHelper::paginated(
                ReviewResource::collection($paginator),
                'تم جلب التقييمات'
            );
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage(), 422);
        }
    }

    public function byBook(string $isbn): JsonResponse
    {
        try {
            return ResponseHelper::success(
                ReviewResource::collection($this->reviews->listByBook($isbn))->resolve(),
                'تم جلب تقييمات الكتاب'
            );
        } catch (\Exception $e) {
            return ResponseHelper::notFound($e->getMessage());
        }
    }

    public function destroy(int $review): JsonResponse
    {
        try {
            $this->reviews->delete(null, $review);

            return ResponseHelper::noContent('تم حذف التقييم');
        } catch (\Exception $e) {
            return ResponseHelper::notFound($e->getMessage());
        }
    }
}
