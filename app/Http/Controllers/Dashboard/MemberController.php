<?php

namespace App\Http\Controllers\Dashboard;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Member\StoreMemberRequest;
use App\Http\Requests\Member\UpdateMemberRequest;
use App\Http\Resources\BorrowingResource;
use App\Http\Resources\LateFineResource;
use App\Http\Resources\UserResource;
use App\Models\Borrowing;
use App\Models\LateFine;
use App\Services\MemberService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MemberController extends Controller
{
    public function __construct(private MemberService $memberService) {}

    public function index(Request $request): JsonResponse
    {
        try {
            $members = $this->memberService->listMembers($request->only(['search']));
            return ResponseHelper::paginated(UserResource::collection($members), 'تم جلب قائمة الأعضاء');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage(), 500);
        }
    }

    public function store(StoreMemberRequest $request): JsonResponse
    {
        try {
            $member = $this->memberService->createMember(
                $request->validated(),
                $request->file('photo_image')
            );
            return ResponseHelper::created(new UserResource($member), 'تم إضافة العضو بنجاح');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage(), 500);
        }
    }

    public function show(int $member): JsonResponse
    {
        try {
            $memberData = $this->memberService->getMember($member);
            return ResponseHelper::success(new UserResource($memberData), 'تم جلب بيانات العضو');
        } catch (\Exception $e) {
            return ResponseHelper::notFound($e->getMessage());
        }
    }

    public function update(UpdateMemberRequest $request, int $member): JsonResponse
    {
        try {
            $updated = $this->memberService->updateMember(
                $member,
                $request->validated(),
                $request->file('photo_image')
            );
            return ResponseHelper::success(new UserResource($updated), 'تم تعديل بيانات العضو بنجاح');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage(), 500);
        }
    }

    public function destroy(int $member): JsonResponse
    {
        try {
            $this->memberService->deleteMember($member);
            return ResponseHelper::noContent('تم حذف العضو بنجاح');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage(), 500);
        }
    }

    public function borrowings(int $member): JsonResponse
    {
        try {
            $memberData = $this->memberService->getMember($member);

            $borrowings = Borrowing::with(['bookInstance.book', 'lateFine'])
                ->where('member_id', $memberData->id)
                ->orderByDesc('id')
                ->paginate(15);

            return ResponseHelper::paginated(
                BorrowingResource::collection($borrowings),
                'تم جلب سجل استعارات العضو'
            );
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage(), 500);
        }
    }

    public function fines(int $member): JsonResponse
    {
        try {
            $memberData = $this->memberService->getMember($member);

            $fines = LateFine::with(['borrowing.member'])
                ->whereHas('borrowing', fn($q) => $q->where('member_id', $memberData->id))
                ->orderByDesc('id')
                ->paginate(15);

            return ResponseHelper::paginated(
                LateFineResource::collection($fines),
                'تم جلب غرامات العضو'
            );
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage(), 500);
        }
    }
}
