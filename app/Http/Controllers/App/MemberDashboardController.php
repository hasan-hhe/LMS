<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Http\Resources\MemberBorrowingResource;
use App\Http\Resources\UserResource;
use App\Models\Borrowing;
use App\Models\LateFine;
use App\Services\PointService;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class MemberDashboardController extends Controller
{
    public function __construct(private PointService $pointService) {}

    #[OA\Get(
        path: '/member/dashboard',
        tags: ['Dashboard'],
        summary: 'Member dashboard',
        description: 'Requires valid token and MEMBER role.',
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Dashboard data retrieved'),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 403, description: 'Not a member account'),
        ]
    )]
    public function dashboard(Request $request)
    {
        $user = $request->user();

        if (! hash_equals($user->role, 'MEMBER')) {
            return response()->json([
                'message' => 'error',
                'body' => 'غير مصرح لك بالوصول',
            ], 403);
        }

        $activeBorrowings = Borrowing::query()
            ->where('member_id', $user->id)
            ->whereNull('returned_at')
            ->with(['bookInstance.book.author'])
            ->orderBy('end_date')
            ->get();

        $overdueCount = $activeBorrowings->filter(fn (Borrowing $b) => $b->isOverdue())->count();
        $unpaidFinePoints = (int) LateFine::whereHas('borrowing', fn ($query) => $query->where('member_id', $user->id))
            ->where('is_paid', false)
            ->sum('fine_points');

        return response()->json([
            'message' => 'success',
            'body' => 'تم جلب البيانات بنجاح',
            'user' => new UserResource($user),
            'borrowed_count' => $activeBorrowings->count(),
            'overdue_count' => $overdueCount,
            'points' => $this->pointService->getBalance($user->id),
            'fines_points' => $unpaidFinePoints,
            'current_borrowings' => MemberBorrowingResource::collection($activeBorrowings),
        ]);
    }
}
