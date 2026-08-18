<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Http\Requests\Borrowing\ExtendBorrowingRequest;
use App\Http\Requests\Borrowing\StoreBorrowingRequest;
use App\Http\Resources\BorrowingResource;
use App\Models\BookInstance;
use App\Models\Borrowing;
use App\Repositories\Interfaces\BorrowingRepositoryInterface;
use App\Services\BorrowingService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BorrowingController extends Controller
{

    public function __construct(
        private BorrowingService $borrowingService,
        // private BorrowingRepositoryInterface $borrowingRepository
    ) {}

    public function borrow(StoreBorrowingRequest $request){
        $user = $request->user();
        if (!hash_equals($user->role, 'LIBRARIAN')) {
            return ResponseHelper::unauthorized();
        }
        
        try {
            $borrowing = $this->borrowingService->checkoutBook(
                $request->validated(),
                $request->user()->id
            );

            return ResponseHelper::created(new BorrowingResource($borrowing), 'تم تسجيل الاستعارة بنجاح');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage(), 422);
        }
    }
        
    public function returnBook(Request $request, int $id){
        $user = $request->user();
        if (!hash_equals($user->role, 'LIBRARIAN')) {
            return ResponseHelper::unauthorized();
        }

        try {
            $borrowing = $this->borrowingService->returnBook($id);
            return ResponseHelper::success(new BorrowingResource($borrowing), 'تم إعادة الكتاب بنجاح');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage(), 422);
        }
    }
    
    public function  index(Request $request, string $order = 'asc'){
        $user = $request->user();
        try {
            $request->validate([
            'parameter' => 'required|string'
        ]);
        }catch(Exception $e){
            return ResponseHelper::validationError('طريقة ترتيب البيانات مطلوبة');
        }
        if (hash_equals($user->role, 'MEMBER')) {
           $borrowings = Borrowing::where('member_id', $user->id)->orderBy($request->parameter, $order)->paginate(15);
           return ResponseHelper::paginated(BorrowingResource::collection($borrowings));
           }
           $borrowings = Borrowing::where('librarian_id', $user->id)->orderBy($request->parameter, $order)->paginate(15);
           return ResponseHelper::paginated(BorrowingResource::collection($borrowings));
    }

    public function extendBorrowing(ExtendBorrowingRequest $request, int $id){
         try {
            $borrowing = $this->borrowingService->extendBorrowing($id, $request->validated());
            return ResponseHelper::success(new BorrowingResource($borrowing), 'تم تمديد الاستعارة بنجاح');
        } catch (Exception $e) {
            return ResponseHelper::error($e->getMessage(), 422);
        }
    }
}
