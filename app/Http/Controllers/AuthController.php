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
    // #[OA\Post(path: "/auth/register", tags: ["Authentication"])]
    public function register(Request $request)
    {
        try {
            $request->validate([
                'first_name' => 'required|string',
                'photo_image' => 'nullable|image|mimes:png,jpg,gif',
                'adress' => 'nullable|string',
                'identity_number' => 'required|unique:users|string|regex:/^[0-9]+$/',
                'last_name' => 'required|string',
                'phone' => 'required|string|unique:users|regex:/^[0-9]+$/',
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

        $photo = null;
        if ($request->hasFile('photo_image'))
            $photo = $request->file('photo_image')->store('Profiles', 'public');
            // $photo = uploadImage($request->file('avatar_image'), 'avatars', 'public');

        try {
            $user = User::create([
                'last_name' => $request->last_name,
                'adress' => $request->adress,
                'phone' => $request->phone,
                'role' => 'MEMBER',
                'email' => $request->email,
                'identity_number' => $request->identity_number,
                'photo_url' => $photo,
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
            'user'  => new UserRecource($user),
            'body'  => 'تم تسجيل الحساب بنجاح'
        ] );
    }

    // #[OA\Post(path: "/auth/login", tags: ["Authentication"])]
    // public function login(Request $request)
    // {
    //     try {
    //         $request->validate([
    //             'phone_number' => 'required|string|regex:/^[0-9]+$/',
    //             'password' => 'required'
    //         ]);
    //     } catch (Exception $e) {
    //         return ResponseHelper::error($e->getMessage(), 422);
    //     }

    //     try {
    //         $user = User::where('phone_number', $request->phone_number)->firstorFail();
    //     } catch (Exception $e) {
    //         return ResponseHelper::error('رقم الهاتف غير موجود', 404);
    //     }

    //     if ($user->status == 'PENDING') {
    //         return ResponseHelper::error('لم يتم الموافقة على حسابك بعد');
    //     }

    //     if (!Hash::check($request->password, $user->password)) {
    //         return ResponseHelper::error('كلمة المرور غير صحيحة', 401);
    //     }

    //     $token = $user->createToken('api_token')->plainTextToken;

    //     return ResponseHelper::success([
    //         'user'  => new UserRecource($user),
    //         'token' => $token,
    //     ], 'تم تسجيل الدخول بنجاح');
    // }


    // // #[OA\Post(path: "/logout", tags: ["Authentication"], security: [["bearerAuth" => []]])]
    // public function logout(Request $request)
    // {
    //     $request->user()->tokens()->delete();
    //     return ResponseHelper::success(null, 'تم تسجيل الخروج بنجاح!');
    // }

}
