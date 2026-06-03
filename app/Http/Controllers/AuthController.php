<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
// use App\Helpers\ResponseHelper;
use App\Http\Resources\UserRecource;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Hash;
// use Illuminate\Support\Str;
// use OpenApi\Attributes as OA;

class AuthController extends Controller
{
    // #[OA\Post(path: "/auth/login", tags: ["Authentication"])]
    public function login(Request $request)
    {
        try {
            $request->validate([
                'phone' => 'required|string|regex:/^[0-9]+$/',
                'email' => 'required|string|email',
                'password' => 'required'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'body' => $e->getMessage()
            ]);
            // return ResponseHelper::error($e->getMessage(), 422);
        }

        try {
            $user = User::where('email', $request->email)->firstorFail();
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'body' => 'البريد الإلكتروني غير موجود'
            ]);
            // return ResponseHelper::error('البريد الإلكتروني غير موجود', 404);
        }
        

        if (!Hash::check($request->password, $user->password_hash)) {
            return response()->json([
                'status' => 'error',
                'body' => 'كلمة المرور غير صحيحة'
            ]);
            // return ResponseHelper::error('كلمة المرور غير صحيحة', 401);
        }

        $token = $user->createToken('api_token')->plainTextToken;
            return response()->json([
                'user'  => new UserRecource($user),
                'token' => $token,
                'body'  => 'تم تسجيل الدخول بنجاح'
            ] );        
        // return ResponseHelper::success([
        //     'user'  => new UserRecource($user),
        //     'token' => $token,
        // ], 'تم تسجيل الدخول بنجاح');composer require laravel/sanctum

    }


    // // #[OA\Post(path: "/logout", tags: ["Authentication"], security: [["bearerAuth" => []]])]
    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();
        return response()->json([
                'body'  => 'تم تسجيل الخروج بنجاح'
            ] );        
         
        // return ResponseHelper::success(null, 'تم تسجيل الخروج بنجاح!');
    }

}
