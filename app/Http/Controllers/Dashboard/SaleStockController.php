<?php

namespace App\Http\Controllers\Dashboard;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Book\AddSaleStockRequest;
use App\Http\Resources\BookResource;
use App\Services\BookService;
use Illuminate\Http\JsonResponse;

class SaleStockController extends Controller
{
    public function __construct(private BookService $bookService) {}

    public function store(AddSaleStockRequest $request): JsonResponse
    {
        try {
            $book = $this->bookService->addSaleStock(
                (string) $request->input('book_ISBN'),
                (int) $request->input('copies_count')
            );

            return ResponseHelper::success(new BookResource($book), 'تم إضافة نسخ البيع');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage(), 422);
        }
    }
}
