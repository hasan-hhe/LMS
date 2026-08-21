<?php

namespace App\Http\Controllers\App\Auth;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use App\Services\AuthService;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use OpenApi\Attributes as OA;

class AuthController extends Controller
{
    public function __construct(private AuthService $authService) {}

    public function register(RegisterRequest $request)
    {
        try {
            $result = $this->authService->register(
                $request->validated(),
                $request->file('photo_image')
            );

            return ResponseHelper::created([
                'user' => new UserResource($result['user']),
                'token' => $result['token'],
            ], 'تم إنشاء حساب العضو بنجاح');
        } catch (\Throwable $e) {
            return ResponseHelper::error('تعذر إنشاء الحساب', 500);
        }
    }

    public function forgotPassword(ForgotPasswordRequest $request)
    {
        $login = trim($request->validated('login'));
        $user = User::query()
            ->where(str_contains($login, '@') ? 'email' : 'phone', $login)
            ->first();

        if ($user) {
            $plainToken = Str::random(64);
            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $user->email],
                ['token' => Hash::make($plainToken), 'created_at' => now()]
            );
            $user->notify(new ResetPasswordNotification($plainToken));
            Log::info('Member password reset requested', [
                'email' => $user->email,
                'link' => url('/reset-password?email='.urlencode($user->email).'&token='.$plainToken),
            ]);
        }

        return ResponseHelper::success(null, 'إذا كان الحساب موجوداً فسيتم إرسال تعليمات استعادة كلمة المرور');
    }

    public function resetPassword(ResetPasswordRequest $request)
    {
        $data = $request->validated();
        $record = DB::table('password_reset_tokens')->where('email', $data['email'])->first();

        if (! $record || ! Hash::check($data['token'], $record->token)
            || now()->diffInMinutes($record->created_at) > 60) {
            return ResponseHelper::error('رمز استعادة كلمة المرور غير صالح أو منتهي الصلاحية', 422);
        }

        $user = User::where('email', $data['email'])->first();
        if (! $user) {
            return ResponseHelper::error('رمز استعادة كلمة المرور غير صالح أو منتهي الصلاحية', 422);
        }

        DB::transaction(function () use ($user, $data): void {
            $user->update(['password_hash' => Hash::make($data['password'])]);
            $user->tokens()->delete();
            DB::table('password_reset_tokens')->where('email', $user->email)->delete();
        });

        return ResponseHelper::success(null, 'تم تغيير كلمة المرور بنجاح');
    }

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
        } else {
            if (!preg_match('/^[0-9]+$/', $login)) {
                return response()->json([
                    'message' => 'error',
                    'body'    => 'رقم الهاتف يجب أن يحتوي على أرقام فقط',
                ], 422);
            }

            $user = User::query()->where('phone', $login)->first();
        }

        $invalidCredentials = 'البريد الإلكتروني أو رقم الهاتف أو كلمة المرور غير صحيحة';

        if (!$user) {
            return response()->json([
                'message' => 'error',
                'body'    => $invalidCredentials,
            ], 401);
        }

        if (!Hash::check($request->password, $user->password_hash)) {
            return response()->json([
                'message' => 'error',
                'body'    => $invalidCredentials,
            ], 401);
        }
        $date1 = $user->participe_end_date;
        if ($user->role == 'MEMBER' && $date1 && $date1->lt(now())) {
            return ResponseHelper::unauthorized('يرجى تجديد الاشتراك، لأن تاريخ اشتراكك منتهي');
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
        return ResponseHelper::success(
            new UserResource($request->user()),
            'تم جلب البيانات بنجاح'
        );
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();
        $data = $request->validate([
            'first_name' => ['sometimes', 'string', 'max:100'],
            'last_name' => ['sometimes', 'string', 'max:100'],
            'phone' => ['sometimes', 'string', 'regex:/^[0-9]+$/', 'unique:users,phone,'.$user->id],
            'adress' => ['sometimes', 'nullable', 'string', 'max:255'],
        ], [
            'phone.regex' => 'رقم الهاتف يجب أن يحتوي على أرقام فقط',
            'phone.unique' => 'رقم الهاتف مستخدم مسبقاً',
        ]);

        $user->update($data);

        return ResponseHelper::success(
            new UserResource($user->fresh()),
            'تم تحديث الملف الشخصي بنجاح'
        );
    }
}
