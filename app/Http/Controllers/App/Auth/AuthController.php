<?php

namespace App\Http\Controllers\App\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use OpenApi\Attributes as OA;

class AuthController extends Controller
{

    #[OA\Post(
        path: '/v1/auth/login',
        tags: ['Authentication'],
        summary: 'Login',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'phone', 'password'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email'),
                    new OA\Property(property: 'phone', type: 'string'),
                    new OA\Property(property: 'password', type: 'string', format: 'password'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Login successful'),
            new OA\Response(response: 401, description: 'Invalid credentials'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
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
            ], 400);
        }

        try {
            $user = User::query()->where('email', $request->email)->firstorFail();
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'body' => 'البريد الإلكتروني غير موجود'
            ], 404);
        }

        if (!Hash::check($request->password, $user->password_hash)) {
            return response()->json([
                'status' => 'error',
                'body' => 'كلمة المرور غير صحيحة'
            ], 400);
        }

        $token = $user->createToken('api_token')->plainTextToken;
        return response()->json([
            'user'  => new UserResource($user),
            'token' => $token,
            'body'  => 'تم تسجيل الدخول بنجاح'
        ]);
    }

    #[OA\Post(
        path: '/v1/auth/logout',
        tags: ['Authentication'],
        summary: 'Logout',
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Logout successful'),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();
        return response()->json([
            'body'  => 'تم تسجيل الخروج بنجاح'
        ]);
    }
}
