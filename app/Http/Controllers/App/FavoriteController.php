<?php

namespace App\Http\Controllers\App;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Favorite\StoreFavoriteRequest;
use App\Http\Resources\FavoriteResource;
use App\Services\FavoriteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    public function __construct(private FavoriteService $favorites) {}

    public function index(Request $request): JsonResponse
    {
        return ResponseHelper::success(
            FavoriteResource::collection($this->favorites->list($request->user()->id)),
            'تم جلب قائمة المفضلة'
        );
    }

    public function store(StoreFavoriteRequest $request): JsonResponse
    {
        try {
            $favorite = $this->favorites->add($request->user()->id, $request->string('isbn')->toString());

            return ResponseHelper::created(new FavoriteResource($favorite), 'تمت إضافة الكتاب إلى المفضلة');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage(), 422);
        }
    }

    public function destroy(Request $request, string $isbn): JsonResponse
    {
        try {
            $this->favorites->remove($request->user()->id, $isbn);

            return ResponseHelper::noContent('تم حذف الكتاب من المفضلة');
        } catch (\Exception $e) {
            return ResponseHelper::notFound($e->getMessage());
        }
    }
}
