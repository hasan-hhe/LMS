<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Http\Resources\MemberBorrowingResource;
use App\Http\Resources\UserResource;
use App\Models\Borrowing;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class MemberDashboardController extends Controller
{
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

        if (!hash_equals($user->role, 'MEMBER')) {
            return response()->json([
                'message' => 'error',
                'body'    => 'غير مصرح لك بالوصول',
            ], 403);
        }

        $activeBorrowings = Borrowing::query()
            ->where('member_id', $user->id)
            ->whereNull('returned_at')
            ->with(['bookInstance.book.author'])
            ->orderBy('end_date')
            ->get();

        $overdueCount = $activeBorrowings->filter(fn (Borrowing $b) => $b->isOverdue())->count();

        return response()->json([
            'message'            => 'success',
            'body'               => 'تم جلب البيانات بنجاح',
            'user'               => new UserResource($user),
            'borrowed_count'     => $activeBorrowings->count(),
            'overdue_count'      => $overdueCount,
            'current_borrowings' => MemberBorrowingResource::collection($activeBorrowings),
        ]);
    }
}
