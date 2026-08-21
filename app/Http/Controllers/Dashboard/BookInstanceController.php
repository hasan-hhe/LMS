<?php

namespace App\Http\Controllers\Dashboard;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\BookInstance\StoreBookInstanceRequest;
use App\Http\Resources\BookInstanceGroupResource;
use App\Http\Resources\BookInstanceResource;
use App\Models\Book;
use App\Models\BookInstance;
use App\Models\Borrowing;
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
                ->when($request->book_isbn, fn ($q) => $q->where('book_ISBN', $request->book_isbn))
                ->when($request->state_id, fn ($q) => $q->where('state_id', $request->state_id))
                ->when($request->search, function ($q) use ($request) {
                    $search = $request->search;
                    $q->whereHas('book', fn ($book) => $book
                        ->where('title', 'like', "%{$search}%")
                        ->orWhere('ISBN', 'like', "%{$search}%"));
                })
                ->latest('id')
                ->paginate(ResponseHelper::perPage($request));

            return ResponseHelper::paginated(
                BookInstanceResource::collection($instances),
                'تم جلب قائمة النسخ'
            );
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage(), 500);
        }
    }

    public function grouped(Request $request): JsonResponse
    {
        try {
            $query = Book::query()
                ->withCount([
                    'instances',
                    'instances as available_count' => fn ($q) => $q->whereHas('state', fn ($state) => $state->where('state', 'available')),
                ])
                ->whereHas('instances', function ($q) use ($request) {
                    if ($request->state_id) {
                        $q->where('state_id', $request->state_id);
                    }
                });

            if ($search = $request->input('search')) {
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                        ->orWhere('ISBN', 'like', "%{$search}%");
                });
            }

            $books = $query->orderBy('title')->paginate(ResponseHelper::perPage($request));

            return ResponseHelper::paginated(
                BookInstanceGroupResource::collection($books),
                'تم جلب مجموعات النسخ'
            );
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage(), 500);
        }
    }

    public function store(StoreBookInstanceRequest $request): JsonResponse
    {
        DB::beginTransaction();
        try {
            $payload = $request->safe()->except(['copies_count']);
            $count = max(1, (int) $request->input('copies_count', 1));
            $created = [];

            for ($i = 0; $i < $count; $i++) {
                $created[] = BookInstance::create($payload);
            }

            $first = $created[0]->load(['book', 'state']);

            DB::commit();

            return ResponseHelper::created(
                new BookInstanceResource($first),
                $count > 1 ? "تم إضافة {$count} نسخ بنجاح" : 'تم إضافة نسخة الكتاب بنجاح'
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

    public function restore(int $id): JsonResponse
    {
        DB::beginTransaction();
        try {
            $instance = BookInstance::with(['state', 'book'])->find($id);
            if (! $instance) {
                return ResponseHelper::notFound('نسخة الكتاب غير موجودة');
            }

            $state = $instance->state?->state;
            if (! in_array($state, ['damaged', 'lost'], true)) {
                DB::rollBack();
                return ResponseHelper::error('يمكن إعادة النسخ التالفة أو المفقودة فقط إلى التداول', 422);
            }

            $activeBorrowing = Borrowing::where('book_instance_id', $instance->id)
                ->whereNull('returned_at')
                ->exists();
            if ($activeBorrowing) {
                DB::rollBack();
                return ResponseHelper::error('لا يمكن إعادة النسخة للتداول وهي ما زالت مستعارة', 422);
            }

            $available = InstanceState::where('state', 'available')->first();
            if (! $available) {
                DB::rollBack();
                return ResponseHelper::error('حالة النسخة المتاحة غير موجودة', 500);
            }

            $instance->update(['state_id' => $available->id]);

            DB::commit();

            return ResponseHelper::success(
                new BookInstanceResource($instance->fresh(['book', 'state'])),
                'تم إعادة النسخة إلى التداول'
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseHelper::error($e->getMessage(), 500);
        }
    }
}
