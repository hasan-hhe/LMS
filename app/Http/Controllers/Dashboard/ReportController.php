<?php

namespace App\Http\Controllers\Dashboard;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Services\ReportService;
use Illuminate\Http\JsonResponse;

class ReportController extends Controller
{
    public function __construct(private ReportService $reportService) {}

    public function overdue(): JsonResponse
    {
        try {
            $data = $this->reportService->getOverdueBorrowings();
            return ResponseHelper::success($data, 'تقرير الاستعارات المتأخرة');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage(), 500);
        }
    }

    public function stats(): JsonResponse
    {
        try {
            $data = $this->reportService->getGeneralStats();
            return ResponseHelper::success($data, 'الإحصاءات العامة');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage(), 500);
        }
    }

    public function mostBorrowed(): JsonResponse
    {
        try {
            $data = $this->reportService->getMostBorrowedBooks();
            return ResponseHelper::success($data, 'تقرير الكتب الأكثر استعارة');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage(), 500);
        }
    }

    public function finesSummary(): JsonResponse
    {
        try {
            $data = $this->reportService->getFinesSummary();
            return ResponseHelper::success($data, 'ملخص الغرامات');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage(), 500);
        }
    }

    public function inventory(): JsonResponse
    {
        try {
            $data = $this->reportService->getInventory();
            return ResponseHelper::success($data, 'تقرير جرد المخزون');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage(), 500);
        }
    }
}
