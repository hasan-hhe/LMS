<?php

namespace App\Http\Controllers\Dashboard;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Author\StoreAuthorRequest;
use App\Http\Resources\AuthorResource;
use App\Models\Author;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AuthorController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Author::withCount('books');

            if ($search = $request->input('search')) {
                $query->where(function ($q) use ($search) {
                    $q->where('firstname', 'like', "%{$search}%")
                        ->orWhere('lastname', 'like', "%{$search}%")
                        ->orWhere('nationality', 'like', "%{$search}%");
                });
            }

            $authors = $query->paginate(15);
            return ResponseHelper::paginated(AuthorResource::collection($authors), 'تم جلب قائمة المؤلفين');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage(), 500);
        }
    }

    public function store(StoreAuthorRequest $request): JsonResponse
    {
        DB::beginTransaction();
        try {
            $author = Author::create($request->validated());
            DB::commit();
            return ResponseHelper::created(new AuthorResource($author), 'تم إضافة المؤلف بنجاح');
        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseHelper::error($e->getMessage(), 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $author = Author::with('books')->find($id);
            if (!$author) {
                return ResponseHelper::notFound('المؤلف غير موجود');
            }
            return ResponseHelper::success(new AuthorResource($author), 'تم جلب بيانات المؤلف');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage(), 500);
        }
    }

    public function update(StoreAuthorRequest $request, int $id): JsonResponse
    {
        DB::beginTransaction();
        try {
            $author = Author::find($id);
            if (!$author) {
                return ResponseHelper::notFound('المؤلف غير موجود');
            }
            $author->update($request->validated());
            DB::commit();
            return ResponseHelper::success(new AuthorResource($author), 'تم تعديل بيانات المؤلف بنجاح');
        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseHelper::error($e->getMessage(), 500);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        DB::beginTransaction();
        try {
            $author = Author::find($id);
            if (!$author) {
                return ResponseHelper::notFound('المؤلف غير موجود');
            }
            $author->delete();
            DB::commit();
            return ResponseHelper::noContent('تم حذف المؤلف بنجاح');
        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseHelper::error($e->getMessage(), 500);
        }
    }
}
