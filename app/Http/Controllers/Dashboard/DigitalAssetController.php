<?php

namespace App\Http\Controllers\Dashboard;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\DigitalAsset\UpsertDigitalAssetRequest;
use App\Http\Resources\DigitalAssetResource;
use App\Services\DigitalAssetService;
use Illuminate\Http\JsonResponse;

class DigitalAssetController extends Controller
{
    public function __construct(private DigitalAssetService $digitalAssets) {}

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
            $asset = $this->digitalAssets->upsert($isbn, $request->validated());

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
