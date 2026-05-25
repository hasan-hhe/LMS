<?php

namespace App\Http\Controllers\Auth;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(private AuthService $authService) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        try {
            $result = $this->authService->register(
                $request->validated(),
                $request->file('photo_image')
            );

            return ResponseHelper::created([
                'user'  => new UserResource($result['user']),
                'token' => $result['token'],
            ], 'تم تسجيل الحساب بنجاح');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage(), 500);
        }
    }

    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $result = $this->authService->login(
                $request->email,
                $request->password
            );

            return ResponseHelper::success([
                'user'  => new UserResource($result['user']),
                'token' => $result['token'],
            ], 'تم تسجيل الدخول بنجاح');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage(), 401);
        }
    }

    public function logout(Request $request): JsonResponse
    {
        try {
            $this->authService->logout($request->user());
            return ResponseHelper::success(null, 'تم تسجيل الخروج بنجاح');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage(), 500);
        }
    }

    public function me(Request $request): JsonResponse
    {
        try {
            return ResponseHelper::success(
                new UserResource($request->user()),
                'تم جلب بيانات المستخدم'
            );
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage(), 500);
        }
    }
}
