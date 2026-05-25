<?php

namespace App\Http\Middleware;

use App\Helpers\ResponseHelper;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        try {
            $user = $request->user();

            if (!$user) {
                return ResponseHelper::unauthorized();
            }

            if (!in_array($user->role, $roles)) {
                return ResponseHelper::forbidden('ليس لديك الصلاحية للوصول لهذا المورد');
            }

            return $next($request);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage(), 500);
        }
    }
}
