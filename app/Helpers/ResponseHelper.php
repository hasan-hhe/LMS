<?php

namespace App\Helpers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ResponseHelper
{
    public static function success(mixed $data = null, string $message = 'تمت العملية بنجاح', int $statusCode = 200): JsonResponse
    {
        return response()->json([
            'status'  => 'success',
            'message' => $message,
            'data'    => $data,
        ], $statusCode);
    }

    public static function created(mixed $data = null, string $message = 'تم الإنشاء بنجاح'): JsonResponse
    {
        return self::success($data, $message, 201);
    }

    public static function error(string $message = 'حدث خطأ ما', int $statusCode = 400, mixed $errors = null): JsonResponse
    {
        $body = [
            'status'  => 'error',
            'message' => $message,
        ];

        if ($errors !== null) {
            $body['errors'] = $errors;
        }

        return response()->json($body, $statusCode);
    }

    public static function notFound(string $message = 'العنصر غير موجود'): JsonResponse
    {
        return self::error($message, 404);
    }

    public static function unauthorized(string $message = 'غير مصرح لك بهذا الإجراء'): JsonResponse
    {
        return self::error($message, 401);
    }

    public static function forbidden(string $message = 'ليس لديك صلاحية'): JsonResponse
    {
        return self::error($message, 403);
    }

    public static function validationError(mixed $errors, string $message = 'بيانات غير صحيحة'): JsonResponse
    {
        return self::error($message, 422, $errors);
    }

    public static function paginated(ResourceCollection $collection, string $message = 'تم جلب البيانات بنجاح'): JsonResponse
    {
        return response()->json([
            'status'  => 'success',
            'message' => $message,
            'data'    => $collection->items(),
            'meta'    => [
                'current_page' => $collection->currentPage(),
                'last_page'    => $collection->lastPage(),
                'per_page'     => $collection->perPage(),
                'total'        => $collection->total(),
            ],
        ]);
    }

    public static function noContent(string $message = 'تم الحذف بنجاح'): JsonResponse
    {
        return response()->json([
            'status'  => 'success',
            'message' => $message,
        ], 200);
    }
}
