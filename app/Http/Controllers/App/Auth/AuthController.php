<?php

namespace App\Http\Controllers\App\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use OpenApi\Attributes as OA;

class AuthController extends Controller
{

    #[OA\Post(
        path: '/v1/auth/login',
        tags: ['Authentication'],
        summary: 'Login',
        description: 'Authenticate with email or phone number and password.',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['login', 'password'],
                properties: [
                    new OA\Property(
                        property: 'login',
                        type: 'string',
                        description: 'Email address or phone number (digits only)'
                    ),
                    new OA\Property(property: 'password', type: 'string', format: 'password'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Login successful'),
            new OA\Response(response: 400, description: 'Incorrect password'),
            new OA\Response(response: 404, description: 'Email or phone not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'login'    => 'required|string',
            'password' => 'required|string',
        ], [
            'login.required'    => 'يرجى إدخال البريد الإلكتروني أو رقم الهاتف',
            'password.required' => 'يرجى إدخال كلمة المرور',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'error',
                'body'    => $validator->errors()->first(),
                'errors'  => $validator->errors(),
            ], 422);
        }

        $login = trim($request->login);
        $isEmail = str_contains($login, '@');

        if ($isEmail) {
            if (!filter_var($login, FILTER_VALIDATE_EMAIL)) {
                return response()->json([
                    'message' => 'error',
                    'body'    => 'يرجى إدخال بريد إلكتروني صحيح',
                ], 422);
            }

            $user = User::query()->where('email', $login)->first();
            $notFoundMessage = 'البريد الإلكتروني غير موجود';
        } else {
            if (!preg_match('/^[0-9]+$/', $login)) {
                return response()->json([
                    'message' => 'error',
                    'body'    => 'رقم الهاتف يجب أن يحتوي على أرقام فقط',
                ], 422);
            }

            $user = User::query()->where('phone', $login)->first();
            $notFoundMessage = 'رقم الهاتف غير موجود';
        }

        if (!$user) {
            return response()->json([
                'message' => 'error',
                'body'    => $notFoundMessage,
            ], 404);
        }

        if (!Hash::check($request->password, $user->password_hash)) {
            return response()->json([
                'message' => 'error',
                'body'    => 'كلمة المرور غير صحيحة',
            ], 400);
        }

        $token = $user->createToken('api_token')->plainTextToken;

        return response()->json([
            'message' => 'success',
            'body'    => 'تم تسجيل الدخول بنجاح',
            'user'    => new UserResource($user),
            'token'   => $token,
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
            'message' => 'success',
            'body'    => 'تم تسجيل الخروج بنجاح',
        ]);
    }

    #[OA\Get(
        path: '/v1/auth/me',
        tags: ['Authentication'],
        summary: 'Get current user',
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'User profile retrieved'),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function me(Request $request)
    {
        return response()->json([
            'message' => 'success',
            'body'    => 'تم جلب البيانات بنجاح',
            'user'    => new UserResource($request->user()),
        ]);
    }
}
