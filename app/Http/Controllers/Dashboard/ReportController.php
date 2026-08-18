<?php

namespace App\Http\Controllers\Dashboard;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Services\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

    public function pointsSummary(): JsonResponse
    {
        try {
            return ResponseHelper::success(
                $this->reportService->getPointsSummary(),
                'ملخص اقتصاد النقاط'
            );
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage(), 500);
        }
    }

    public function pointsExport(Request $request): StreamedResponse
    {
        $transactions = $this->reportService->getPointTransactionsForExport(
            $request->only(['user_id', 'type', 'from', 'to', 'limit'])
        );

        return $this->csvDownload('point-transactions.csv', [
            'date', 'user_id', 'email', 'points', 'type', 'note',
        ], $transactions->map(fn ($transaction) => [
            $transaction->created_at?->format('Y-m-d H:i:s'),
            $transaction->user_id,
            $transaction->user?->email,
            $transaction->points,
            $transaction->type,
            $transaction->note,
        ]));
    }

    public function finesExport(Request $request): StreamedResponse
    {
        $fines = $this->reportService->getFinesForExport((int) $request->input('limit', 1000));

        return $this->csvDownload('fines.csv', [
            'id', 'borrowing_id', 'user_id', 'email', 'days_late', 'fine', 'fine_points', 'is_paid', 'paid_at',
        ], $fines->map(fn ($fine) => [
            $fine->id,
            $fine->borrowing_id,
            $fine->borrowing?->member_id,
            $fine->borrowing?->member?->email,
            $fine->days_late,
            $fine->fine,
            $fine->fine_points,
            $fine->is_paid ? 1 : 0,
            $fine->paid_at?->format('Y-m-d H:i:s'),
        ]));
    }

    public function overdueExport(Request $request): StreamedResponse
    {
        $borrowings = $this->reportService->getOverdueForExport((int) $request->input('limit', 1000));

        return $this->csvDownload('overdue-borrowings.csv', [
            'id', 'user_id', 'email', 'book_isbn', 'book_title', 'end_date', 'days_overdue',
        ], $borrowings->map(fn ($borrowing) => [
            $borrowing->id,
            $borrowing->member_id,
            $borrowing->member?->email,
            $borrowing->bookInstance?->book_ISBN,
            $borrowing->bookInstance?->book?->title,
            $borrowing->end_date?->format('Y-m-d'),
            now()->diffInDays($borrowing->end_date),
        ]));
    }

    private function csvDownload(string $filename, array $headers, iterable $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $rows) {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, $headers);
            foreach ($rows as $row) {
                fputcsv($output, $row);
            }
            fclose($output);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
