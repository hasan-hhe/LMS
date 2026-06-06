<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserRecource;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class MemberController extends Controller
{
    // #[OA\Post(path: "/auth/register", tags: ["Authentication"])]
    public function register(Request $request)
    {
        $user = $request->user();
        if(!hash_equals($user->role, 'LIBRARIAN')){
            return response()->json([
                'body' => 'عذرا انت لا تملك الصلاحية لذلك'
            ]);
        }
        try {
            $request->validate([
                'first_name' => 'required|string',
                'photo_image' => 'nullable|image|mimes:png,jpg,gif',
                'adress' => 'nullable|string',
                'identity_number' => 'required|unique:users|string|regex:/^[0-9]+$/',
                'last_name' => 'required|string',
                'participe_end_date' => 'required|date',
                'phone' => 'required|string|regex:/^[0-9]+$/',
                'email' => 'required|string|email|unique:users',
                'password' => 'required|min:6'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'body'   => $e->getMessage()
            ]);
            // return ResponseHelper::error($e->getMessage(), 422);
        }

        $date = Carbon::parse($request->participe_end_date);
        
        $photo = null;
        if ($request->hasFile('photo_image'))
            $photo = $request->file('photo_image')->store('Profiles', 'public');
        // $photo = uploadImage($request->file('avatar_image'), 'avatars', 'public');
        
        try {
            $user2 = User::create([
                'last_name' => $request->last_name,
                'adress' => $request->adress,
                'phone' => $request->phone,
                'role' => 'MEMBER',
                'email' => $request->email,
                'identity_number' => $request->identity_number,
                'photo_url' => $photo,
                'participe_end_date' => $date,
                'password_hash' => Hash::make($request->password),
                'first_name' => $request->first_name
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'body' => $e->getMessage()
            ]);
            // return  ResponseHelper::error($e->getMessage(), 400);
        }

        // return ResponseHelper::success([
        return response()->json([
            'user'  => new UserRecource($user2),
            'body'  => 'تم تسجيل الحساب بنجاح'
        ] );
    }

    public function index(Request $request)
    {
        $user = $request->user();
        if(!hash_equals($user->role, 'LIBRARIAN')){
            return response()->json([
                'body' => 'عذرا انت لا تملك الصلاحية لذلك'
            ]);
        }
        $members = User::where('role', 'MEMBER')->get();
        return response()->json([
            'user'  => UserRecource::collection($members),
        ] );
    }

    public function updateMember(Request $request, $id)
    {
        $user = $request->user();
        if(!hash_equals($user->role, 'LIBRARIAN')){
            return response()->json([
                'body' => 'عذرا انت لا تملك الصلاحية لذلك'
            ]);
        }
        try {
            $request->validate([
                'first_name' => 'nullable|string',
                'photo_image' => 'nullable|image|mimes:png,jpg,gif',
                'adress' => 'nullable|string',
                'identity_number' => 'nullable|unique:users|string|regex:/^[0-9]+$/',
                'last_name' => 'nullable|string',
                'participe_end_date' => 'nullable|date',
                'phone' => 'nullable|string|regex:/^[0-9]+$/',
                'email' => 'nullable|string|email|unique:users',
                'password' => 'nullable|min:6'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'body'   => $e->getMessage()
            ]);
            // return ResponseHelper::error($e->getMessage(), 422);
        }

        $date = Carbon::parse($request->participe_end_date);
        
        $photo = null;
        if ($request->hasFile('photo_image'))
            $photo = $request->file('photo_image')->store('Profiles', 'public');
        // $photo = uploadImage($request->file('avatar_image'), 'avatars', 'public');
        
        try {
            $user2 = User::where('id', $id)->firstOrFail();
            $user2->update([
                'last_name' => $request->last_name,
                'adress' => $request->adress,
                'phone' => $request->phone,
                'email' => $request->email,
                'identity_number' => $request->identity_number,
                'photo_url' => $photo,
                'participe_end_date' => $date,
                'password_hash' => Hash::make($request->password),
                'first_name' => $request->first_name
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'body' => $e->getMessage()
            ]);
            // return  ResponseHelper::error($e->getMessage(), 400);
        }

        // return ResponseHelper::success([
        return response()->json([
            'user'  => new UserRecource($user2),
            'body'  => 'تم تحديث الحساب بنجاح'
        ] );
        // Similar validation and update logic as register method
    }

    public function ControlAccountState(Request $request, $id){
        $user = $request->user();
        if(!hash_equals($user->role, 'LIBRARIAN')){
            return response()->json([
                'body' => 'عذرا انت لا تملك الصلاحية لذلك'
                ]);
                }
        $request->validate([
       'state' => 'required|in:ACTIVE, PAUSED, CANCLED'
                ]);
        User::Where('id', $id)->update([
            'state' => $request->state
        ]);
         return response()->json([
            'body' => 'تم تغيير حالة الحساب بنجاح'
        ]);
    }

    public function updateParticipeDate(Request $request, $id){
        $user = $request->user();
        if(!hash_equals($user->role, 'LIBRARIAN')){
            return response()->json([
                'body' => 'عذرا انت لا تملك الصلاحية لذلك'
            ]);
        }
        $request->validate([
            'participe_end_date' => 'required|date'
        ]);
        $date = Carbon::parse($request->participe_end_date);
        User::where('id', $id)->update([
            'participe_end_date' => $date
        ]);
        return response()->json([   
            'user'  => new UserRecource($user),
            'body'  => 'تم تحديث تاريخ الاشتراك بنجاح'
        ] );    
    }

    public function get(Request $request, $id){
        $user = $request->user();
        if(!hash_equals($user->role, 'LIBRARIAN')){
            return response()->json([
                'body' => 'عذرا انت لا تملك الصلاحية لذلك'
            ]);
        }
        try {
        $user2 = User::where('id', $id)->firstOrFail();
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'body' => 'المستخدم غير موجود'
            ]);
            // return ResponseHelper::error('المستخدم غير موجود', 404);
        }
        return response()->json([
            'user'  => new UserRecource($user2),
            'body'  => 'تم إيجاد المستخدم بنجاح'
        ] );
    }
}
