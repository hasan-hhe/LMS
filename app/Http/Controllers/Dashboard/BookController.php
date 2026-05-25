<?php

namespace App\Http\Controllers\Dashboard;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Book\StoreBookRequest;
use App\Http\Requests\Book\UpdateBookRequest;
use App\Http\Resources\BookResource;
use App\Services\BookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookController extends Controller
{
    public function __construct(private BookService $bookService) {}

    public function index(Request $request): JsonResponse
    {
        try {
            $filters = $request->only(['search', 'category_id', 'author_id', 'year']);
            $books   = $this->bookService->listBooks($filters);

            return ResponseHelper::paginated(
                BookResource::collection($books),
                'تم جلب قائمة الكتب'
            );
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage(), 500);
        }
    }

    public function store(StoreBookRequest $request): JsonResponse
    {
        try {
            $book = $this->bookService->createBook(
                $request->validated(),
                $request->file('cover_image')
            );

            return ResponseHelper::created(new BookResource($book), 'تم إضافة الكتاب بنجاح');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage(), 500);
        }
    }

    public function show(string $isbn): JsonResponse
    {
        try {
            $book = $this->bookService->getBook($isbn);
            return ResponseHelper::success(new BookResource($book), 'تم جلب بيانات الكتاب');
        } catch (\Exception $e) {
            return ResponseHelper::notFound($e->getMessage());
        }
    }

    public function update(UpdateBookRequest $request, string $isbn): JsonResponse
    {
        try {
            $book = $this->bookService->updateBook(
                $isbn,
                $request->validated(),
                $request->file('cover_image')
            );

            return ResponseHelper::success(new BookResource($book), 'تم تعديل بيانات الكتاب بنجاح');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage(), 500);
        }
    }

    public function destroy(string $isbn): JsonResponse
    {
        try {
            $this->bookService->deleteBook($isbn);
            return ResponseHelper::noContent('تم حذف الكتاب بنجاح');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage(), 500);
        }
    }
}
