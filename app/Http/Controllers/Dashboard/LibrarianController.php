<?php

namespace App\Http\Controllers\Dashboard;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Librarian\StoreLibrarianRequest;
use App\Http\Requests\Librarian\UpdateLibrarianRequest;
use App\Http\Resources\UserResource;
use App\Services\LibrarianService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LibrarianController extends Controller
{
    public function __construct(private LibrarianService $librarianService) {}

    public function index(Request $request): JsonResponse
    {
        try {
            $librarians = $this->librarianService->listLibrarians($request->only(['search']));
            return ResponseHelper::paginated(UserResource::collection($librarians), 'تم جلب قائمة أمناء المكتبة');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage(), 500);
        }
    }

    public function store(StoreLibrarianRequest $request): JsonResponse
    {
        try {
            $librarian = $this->librarianService->createLibrarian(
                $request->validated(),
                $request->file('photo_image')
            );
            return ResponseHelper::created(new UserResource($librarian), 'تم إضافة أمين المكتبة بنجاح');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage(), 500);
        }
    }

    public function show(int $librarian): JsonResponse
    {
        try {
            $librarianData = $this->librarianService->getLibrarian($librarian);
            return ResponseHelper::success(new UserResource($librarianData), 'تم جلب بيانات أمين المكتبة');
        } catch (\Exception $e) {
            return ResponseHelper::notFound($e->getMessage());
        }
    }

    public function update(UpdateLibrarianRequest $request, int $librarian): JsonResponse
    {
        try {
            $updated = $this->librarianService->updateLibrarian(
                $librarian,
                $request->validated(),
                $request->file('photo_image')
            );
            return ResponseHelper::success(new UserResource($updated), 'تم تعديل بيانات أمين المكتبة بنجاح');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage(), 500);
        }
    }

    public function destroy(int $librarian): JsonResponse
    {
        try {
            $this->librarianService->deleteLibrarian($librarian);
            return ResponseHelper::noContent('تم حذف أمين المكتبة بنجاح');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage(), 500);
        }
    }
}
