<?php

namespace App\Http\Controllers\Dashboard;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Favorite\ListFavoritesRequest;
use App\Http\Resources\FavoriteResource;
use App\Models\User;
use App\Services\FavoriteService;
use Illuminate\Http\JsonResponse;

class FavoriteController extends Controller
{
    public function __construct(private FavoriteService $favorites) {}

    public function index(ListFavoritesRequest $request): JsonResponse
    {
        if (! User::whereKey($request->integer('member_id'))->where('role', 'MEMBER')->exists()) {
            return ResponseHelper::notFound('العضو غير موجود');
        }

        return ResponseHelper::success(
            FavoriteResource::collection($this->favorites->list($request->integer('member_id'))),
            'تم جلب مفضلة العضو'
        );
    }
}
