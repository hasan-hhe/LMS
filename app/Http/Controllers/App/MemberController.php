<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use OpenApi\Attributes as OA;

class MemberController extends Controller
{
    public function register(Request $request)
    {
        $user = $request->user();
        if (!hash_equals($user->role, 'LIBRARIAN')) {
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
            'user'  => new UserResource($user2),
            'body'  => 'تم تسجيل الحساب بنجاح'
        ]);
    }

    #[OA\Post(
        path: '/member/get-members',
        tags: ['Members'],
        summary: 'List all members',
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Members retrieved successfully'),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 403, description: 'Insufficient permissions'),
        ]
    )]
    public function index(Request $request)
    {
        $user = $request->user();
        if (!hash_equals($user->role, 'LIBRARIAN')) {
            return response()->json([
                'body' => 'عذرا انت لا تملك الصلاحية لذلك'
            ]);
        }
        $members = User::query()->where('role', 'MEMBER')->get();
        return response()->json([
            'user'  => UserResource::collection($members),
        ]);
    }

    #[OA\Post(
        path: '/member/update-member/{id}',
        tags: ['Members'],
        summary: 'Update member',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(properties: [
                    new OA\Property(property: 'first_name', type: 'string', nullable: true),
                    new OA\Property(property: 'last_name', type: 'string', nullable: true),
                    new OA\Property(property: 'email', type: 'string', format: 'email', nullable: true),
                    new OA\Property(property: 'phone', type: 'string', nullable: true),
                    new OA\Property(property: 'identity_number', type: 'string', nullable: true),
                    new OA\Property(property: 'password', type: 'string', format: 'password', nullable: true),
                    new OA\Property(property: 'adress', type: 'string', nullable: true),
                    new OA\Property(property: 'participe_end_date', type: 'string', format: 'date', nullable: true),
                    new OA\Property(property: 'photo_image', type: 'string', format: 'binary', nullable: true),
                ])
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Member updated successfully'),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 403, description: 'Insufficient permissions'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function updateMember(Request $request, $id)
    {
        $user = $request->user();
        if (!hash_equals($user->role, 'LIBRARIAN')) {
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
            $user2 = User::query()->where('id', $id)->firstOrFail();
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
            'user'  => new UserResource($user2),
            'body'  => 'تم تحديث الحساب بنجاح'
        ]);
        // Similar validation and update logic as register method
    }

    #[OA\Post(
        path: '/member/control-state/{id}',
        tags: ['Members'],
        summary: 'Change member account state',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['state'],
                properties: [
                    new OA\Property(property: 'state', type: 'string', enum: ['ACTIVE', 'PAUSED', 'CANCLED']),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Account state updated'),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 403, description: 'Insufficient permissions'),
            new OA\Response(response: 422, description: 'Invalid state value'),
        ]
    )]
    public function ControlAccountState(Request $request, $id)
    {
        $user = $request->user();
        if (!hash_equals($user->role, 'LIBRARIAN')) {
            return response()->json([
                'body' => 'عذرا انت لا تملك الصلاحية لذلك'
            ]);
        }
        $request->validate([
            'state' => 'required|in:ACTIVE, PAUSED, CANCLED'
        ]);
        User::query()->where('id', $id)->update([
            'state' => $request->state
        ]);
        return response()->json([
            'body' => 'تم تغيير حالة الحساب بنجاح'
        ]);
    }

    #[OA\Post(
        path: '/member/update-participe-date/{id}',
        tags: ['Members'],
        summary: 'Update membership expiry date',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['participe_end_date'],
                properties: [
                    new OA\Property(property: 'participe_end_date', type: 'string', format: 'date'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Membership date updated'),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 403, description: 'Insufficient permissions'),
            new OA\Response(response: 422, description: 'Invalid date'),
        ]
    )]
    public function updateParticipeDate(Request $request, $id)
    {
        $user = $request->user();
        if (!hash_equals($user->role, 'LIBRARIAN')) {
            return response()->json([
                'body' => 'عذرا انت لا تملك الصلاحية لذلك'
            ]);
        }
        $request->validate([
            'participe_end_date' => 'required|date'
        ]);
        $date = Carbon::parse($request->participe_end_date);
        User::query()->where('id', $id)->update([
            'participe_end_date' => $date
        ]);
        return response()->json([
            'user'  => new UserResource($user),
            'body'  => 'تم تحديث تاريخ الاشتراك بنجاح'
        ]);
    }

    #[OA\Post(
        path: '/member/get/{id}',
        tags: ['Members'],
        summary: 'Get member by ID',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Member found'),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 403, description: 'Insufficient permissions'),
            new OA\Response(response: 404, description: 'Member not found'),
        ]
    )]
    public function get(Request $request, string $id)
    {
        $user = $request->user();
        if (!hash_equals($user->role, 'LIBRARIAN')) {
            return response()->json([
                'body' => 'عذرا انت لا تملك الصلاحية لذلك'
            ]);
        }
        try {
            $user2 = User::query()->where('id', $id)->firstOrFail();
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'body' => 'المستخدم غير موجود'
            ]);
            // return ResponseHelper::error('المستخدم غير موجود', 404);
        }
        return response()->json([
            'user'  => new UserResource($user2),
            'body'  => 'تم إيجاد المستخدم بنجاح'
        ]);
    }
}
