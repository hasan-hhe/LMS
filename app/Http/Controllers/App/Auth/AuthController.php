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
        path: '/v1/auth/register',
        tags: ['Authentication'],
        summary: 'Register a new account',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['first_name', 'last_name', 'email', 'phone', 'identity_number', 'password'],
                    properties: [
                        new OA\Property(property: 'first_name', type: 'string'),
                        new OA\Property(property: 'last_name', type: 'string'),
                        new OA\Property(property: 'email', type: 'string', format: 'email'),
                        new OA\Property(property: 'phone', type: 'string'),
                        new OA\Property(property: 'identity_number', type: 'string'),
                        new OA\Property(property: 'password', type: 'string', format: 'password'),
                        new OA\Property(property: 'adress', type: 'string', nullable: true),
                        new OA\Property(property: 'photo_image', type: 'string', format: 'binary', nullable: true),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Account created successfully'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function register(Request $request)
    {
        try {
            $request->validate([
                'first_name' => 'required|string',
                'photo_image' => 'nullable|image|mimes:png,jpg,gif',
                'adress' => 'nullable|string',
                'identity_number' => 'required|unique:users|string|regex:/^[0-9]+$/',
                'last_name' => 'required|string',
                'phone' => 'required|string|regex:/^[0-9]+$/',
                'email' => 'required|string|email|unique:users',
                'password' => 'required|min:6'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'body'   => $e->getMessage()
            ]);
        }

        $photo = null;
        if ($request->hasFile('photo_image'))
            $photo = $request->file('photo_image')->store('Profiles', 'public');

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
        }

        return response()->json([
            'user'  => new UserResource($user),
            'body'  => 'تم تسجيل الحساب بنجاح'
        ]);
    }

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
            ]);
        }

        try {
            $user = User::query()->where('email', $request->email)->firstorFail();
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'body' => 'البريد الإلكتروني غير موجود'
            ]);
        }

        if (!Hash::check($request->password, $user->password_hash)) {
            return response()->json([
                'status' => 'error',
                'body' => 'كلمة المرور غير صحيحة'
            ]);
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
