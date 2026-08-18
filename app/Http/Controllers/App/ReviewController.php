<?php

namespace App\Http\Controllers\App;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Review\StoreReviewRequest;
use App\Http\Resources\ReviewResource;
use App\Services\ReviewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function __construct(private ReviewService $reviews) {}

    public function mine(Request $request): JsonResponse
    {
        return ResponseHelper::success(
            ReviewResource::collection($this->reviews->listByUser($request->user()->id)),
            'تم جلب تقييماتك'
        );
    }

    public function store(StoreReviewRequest $request): JsonResponse
    {
        try {
            $review = $this->reviews->createOrUpdate(
                $request->user()->id,
                $request->string('isbn')->toString(),
                $request->integer('rate'),
                $request->input('comment')
            );

            return ResponseHelper::success(new ReviewResource($review), 'تم حفظ التقييم');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage(), 422);
        }
    }

    public function destroy(Request $request, int $review): JsonResponse
    {
        try {
            $this->reviews->delete($request->user()->id, $review);

            return ResponseHelper::noContent('تم حذف التقييم');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage(), 403);
        }
    }
}
