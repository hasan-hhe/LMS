<?php

namespace App\Helpers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ResponseHelper
{
    public static function success(mixed $data = null, string $body = 'تمت العملية بنجاح', int $statusCode = 200): JsonResponse
    {
        $response = [
            'message' => 'success',
            'body'    => $body,
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        return response()->json($response, $statusCode);
    }

    public static function created(mixed $data = null, string $body = 'تم الإنشاء بنجاح'): JsonResponse
    {
        return self::success($data, $body, 201);
    }

    public static function error(string $body = 'حدث خطأ ما', int $statusCode = 400, mixed $errors = null): JsonResponse
    {
        $response = [
            'message' => 'error',
            'body'    => $body,
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $statusCode);
    }

    public static function notFound(string $body = 'العنصر غير موجود'): JsonResponse
    {
        return self::error($body, 404);
    }

    public static function unauthorized(string $body = 'غير مصرح لك بهذا الإجراء'): JsonResponse
    {
        return self::error($body, 401);
    }

    public static function forbidden(string $body = 'ليس لديك صلاحية'): JsonResponse
    {
        return self::error($body, 403);
    }

    public static function validationError(mixed $errors, string $body = 'بيانات غير صحيحة'): JsonResponse
    {
        return self::error($body, 422, $errors);
    }

    public static function paginated(ResourceCollection $collection, string $body = 'تم جلب البيانات بنجاح'): JsonResponse
    {
        return response()->json([
            'message' => 'success',
            'body'    => $body,
            'data'    => $collection->items(),
            'meta'    => [
                'current_page' => $collection->currentPage(),
                'last_page'    => $collection->lastPage(),
                'per_page'     => $collection->perPage(),
                'total'        => $collection->total(),
            ],
        ]);
    }

    public static function noContent(string $body = 'تم الحذف بنجاح'): JsonResponse
    {
        return response()->json([
            'message' => 'success',
            'body'    => $body,
        ], 200);
    }
}
