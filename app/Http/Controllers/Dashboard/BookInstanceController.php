<?php

namespace App\Http\Controllers\Dashboard;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\BookInstance\StoreBookInstanceRequest;
use App\Http\Resources\BookInstanceResource;
use App\Models\BookInstance;
use App\Models\InstanceState;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BookInstanceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        try {
            $instances = BookInstance::with(['book', 'state'])
                ->when($request->book_isbn, fn($q) => $q->where('book_ISBN', $request->book_isbn))
                ->when($request->state_id, fn($q) => $q->where('state_id', $request->state_id))
                ->paginate(15);

            return ResponseHelper::paginated(
                BookInstanceResource::collection($instances),
                'تم جلب قائمة النسخ'
            );
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage(), 500);
        }
    }

    public function store(StoreBookInstanceRequest $request): JsonResponse
    {
        DB::beginTransaction();
        try {
            $instance = BookInstance::create($request->validated());

            DB::commit();
            return ResponseHelper::created(
                new BookInstanceResource($instance->load(['book', 'state'])),
                'تم إضافة نسخة الكتاب بنجاح'
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseHelper::error($e->getMessage(), 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $instance = BookInstance::with(['book', 'state'])->find($id);
            if (!$instance) {
                return ResponseHelper::notFound('نسخة الكتاب غير موجودة');
            }
            return ResponseHelper::success(new BookInstanceResource($instance), 'تم جلب بيانات النسخة');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage(), 500);
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        DB::beginTransaction();
        try {
            $instance = BookInstance::find($id);
            if (!$instance) {
                return ResponseHelper::notFound('نسخة الكتاب غير موجودة');
            }

            $validated = $request->validate([
                'state_id'  => 'sometimes|integer|exists:instance_states,id',
                'condition' => 'sometimes|in:new,worn,almost_new',
            ]);

            $instance->update($validated);

            DB::commit();
            return ResponseHelper::success(
                new BookInstanceResource($instance->fresh(['book', 'state'])),
                'تم تعديل النسخة بنجاح'
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseHelper::error($e->getMessage(), 500);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        DB::beginTransaction();
        try {
            $instance = BookInstance::find($id);
            if (!$instance) {
                return ResponseHelper::notFound('نسخة الكتاب غير موجودة');
            }

            $instance->delete();

            DB::commit();
            return ResponseHelper::noContent('تم حذف النسخة بنجاح');
        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseHelper::error($e->getMessage(), 500);
        }
    }
}
