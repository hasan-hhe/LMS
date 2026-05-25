<?php

namespace App\Http\Controllers\Dashboard;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Category\StoreCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CategoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Category::withCount('books');

            if ($search = $request->input('search')) {
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                        ->orWhere('discription', 'like', "%{$search}%");
                });
            }

            $categories = $query->paginate(15);
            return ResponseHelper::paginated(CategoryResource::collection($categories), 'تم جلب قائمة التصنيفات');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage(), 500);
        }
    }

    public function store(StoreCategoryRequest $request): JsonResponse
    {
        DB::beginTransaction();
        try {
            $category = Category::create($request->validated());
            DB::commit();
            return ResponseHelper::created(new CategoryResource($category), 'تم إضافة التصنيف بنجاح');
        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseHelper::error($e->getMessage(), 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $category = Category::with('books')->find($id);
            if (!$category) {
                return ResponseHelper::notFound('التصنيف غير موجود');
            }
            return ResponseHelper::success(new CategoryResource($category), 'تم جلب بيانات التصنيف');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage(), 500);
        }
    }

    public function update(StoreCategoryRequest $request, int $id): JsonResponse
    {
        DB::beginTransaction();
        try {
            $category = Category::find($id);
            if (!$category) {
                return ResponseHelper::notFound('التصنيف غير موجود');
            }
            $category->update($request->validated());
            DB::commit();
            return ResponseHelper::success(new CategoryResource($category), 'تم تعديل التصنيف بنجاح');
        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseHelper::error($e->getMessage(), 500);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        DB::beginTransaction();
        try {
            $category = Category::find($id);
            if (!$category) {
                return ResponseHelper::notFound('التصنيف غير موجود');
            }
            $category->delete();
            DB::commit();
            return ResponseHelper::noContent('تم حذف التصنيف بنجاح');
        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseHelper::error($e->getMessage(), 500);
        }
    }
}
