<?php

namespace App\Traits;

use App\Helpers\ResponseHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\ResourceCollection;

trait ApiResponseTrait
{
    protected function successResponse(mixed $data = null, string $message = 'تمت العملية بنجاح', int $statusCode = 200): JsonResponse
    {
        return ResponseHelper::success($data, $message, $statusCode);
    }

    protected function createdResponse(mixed $data = null, string $message = 'تم الإنشاء بنجاح'): JsonResponse
    {
        return ResponseHelper::created($data, $message);
    }

    protected function errorResponse(string $message = 'حدث خطأ ما', int $statusCode = 400, mixed $errors = null): JsonResponse
    {
        return ResponseHelper::error($message, $statusCode, $errors);
    }

    protected function notFoundResponse(string $message = 'العنصر غير موجود'): JsonResponse
    {
        return ResponseHelper::notFound($message);
    }

    protected function forbiddenResponse(string $message = 'ليس لديك صلاحية'): JsonResponse
    {
        return ResponseHelper::forbidden($message);
    }

    protected function paginatedResponse(ResourceCollection $collection, string $message = 'تم جلب البيانات بنجاح'): JsonResponse
    {
        return ResponseHelper::paginated($collection, $message);
    }

    protected function deletedResponse(string $message = 'تم الحذف بنجاح'): JsonResponse
    {
        return ResponseHelper::noContent($message);
    }
}
