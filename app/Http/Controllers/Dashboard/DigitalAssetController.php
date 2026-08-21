<?php

namespace App\Http\Controllers\Dashboard;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\DigitalAsset\UpsertDigitalAssetRequest;
use App\Http\Resources\DigitalAssetResource;
use App\Services\DigitalAssetService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DigitalAssetController extends Controller
{
    public function __construct(private DigitalAssetService $digitalAssets) {}

    public function index(Request $request): JsonResponse
    {
        try {
            $assets = $this->digitalAssets->list($request->only(['search', 'per_page']));

            return ResponseHelper::paginated(
                DigitalAssetResource::collection($assets),
                'تم جلب المحتوى الرقمي'
            );
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage(), 500);
        }
    }

    public function store(UpsertDigitalAssetRequest $request): JsonResponse
    {
        try {
            $isbn = (string) $request->input('book_ISBN');
            $asset = $this->digitalAssets->upsert(
                $isbn,
                $request->safe()->except(['pdf', 'audio', 'book_ISBN']),
                $request->file('pdf'),
                $request->file('audio'),
            );

            return ResponseHelper::created(new DigitalAssetResource($asset), 'تم حفظ المحتوى الرقمي');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage(), 422);
        }
    }

    public function show(string $isbn): JsonResponse
    {
        try {
            $asset = $this->digitalAssets->findByIsbn($isbn);

            return ResponseHelper::success(new DigitalAssetResource($asset), 'تم جلب المحتوى الرقمي');
        } catch (\Exception $e) {
            return ResponseHelper::notFound($e->getMessage());
        }
    }

    public function upsert(UpsertDigitalAssetRequest $request, string $isbn): JsonResponse
    {
        try {
            $asset = $this->digitalAssets->upsert(
                $isbn,
                $request->safe()->except(['pdf', 'audio']),
                $request->file('pdf'),
                $request->file('audio'),
            );

            return ResponseHelper::success(new DigitalAssetResource($asset), 'تم حفظ المحتوى الرقمي');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage(), 422);
        }
    }

    public function destroy(string $isbn): JsonResponse
    {
        try {
            $this->digitalAssets->delete($isbn);

            return ResponseHelper::noContent('تم حذف المحتوى الرقمي');
        } catch (\Exception $e) {
            return ResponseHelper::notFound($e->getMessage());
        }
    }
}
