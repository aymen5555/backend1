<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
        } catch (JWTException) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Token missing or invalid.',
            ], 401);
        }

        if (! $user || ! $this->roleMatches($user->role, $roles)) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden. You do not have permission to access this resource.',
            ], 403);
        }

        return $next($request);
    }

    private function roleMatches(string $userRole, array $roles): bool
    {
        $normalizedUserRole = User::normalizeRole($userRole);
        $normalizedRoles = array_map([User::class, 'normalizeRole'], $roles);

        return in_array($normalizedUserRole, $normalizedRoles, true);
    }
}
