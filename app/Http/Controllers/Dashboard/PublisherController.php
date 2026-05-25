<?php

namespace App\Http\Controllers\Dashboard;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Publisher\StorePublisherRequest;
use App\Http\Resources\PublisherResource;
use App\Models\Publisher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PublisherController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Publisher::withCount('books');

            if ($search = $request->input('search')) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('location', 'like', "%{$search}%");
                });
            }

            $publishers = $query->paginate(15);
            return ResponseHelper::paginated(PublisherResource::collection($publishers), 'تم جلب قائمة دور النشر');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage(), 500);
        }
    }

    public function store(StorePublisherRequest $request): JsonResponse
    {
        DB::beginTransaction();
        try {
            $publisher = Publisher::create($request->validated());
            DB::commit();
            return ResponseHelper::created(new PublisherResource($publisher), 'تم إضافة دار النشر بنجاح');
        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseHelper::error($e->getMessage(), 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $publisher = Publisher::with('books')->find($id);
            if (!$publisher) {
                return ResponseHelper::notFound('دار النشر غير موجودة');
            }
            return ResponseHelper::success(new PublisherResource($publisher), 'تم جلب بيانات دار النشر');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage(), 500);
        }
    }

    public function update(StorePublisherRequest $request, int $id): JsonResponse
    {
        DB::beginTransaction();
        try {
            $publisher = Publisher::find($id);
            if (!$publisher) {
                return ResponseHelper::notFound('دار النشر غير موجودة');
            }
            $publisher->update($request->validated());
            DB::commit();
            return ResponseHelper::success(new PublisherResource($publisher), 'تم تعديل دار النشر بنجاح');
        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseHelper::error($e->getMessage(), 500);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        DB::beginTransaction();
        try {
            $publisher = Publisher::find($id);
            if (!$publisher) {
                return ResponseHelper::notFound('دار النشر غير موجودة');
            }
            $publisher->delete();
            DB::commit();
            return ResponseHelper::noContent('تم حذف دار النشر بنجاح');
        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseHelper::error($e->getMessage(), 500);
        }
    }
}
