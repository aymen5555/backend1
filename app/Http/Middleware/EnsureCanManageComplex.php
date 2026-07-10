<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCanManageComplex
{
    /**
     * Handle an incoming request.
     *
     * Ensures the authenticated user can manage the complex specified in the route parameter.
     * SUPER_ADMIN can manage any complex; GERANT can only manage their own.
     *
     * Usage in route: ->middleware('complexe.manage:complexe_id_param')
     * Example: ->middleware('complexe.manage:complexe')
     */
    public function handle(Request $request, Closure $next, ?string $complexeParam = 'complexe'): Response
    {
        $user = auth()->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $complexe = $request->route($complexeParam);

        if (! $complexe) {
            return response()->json(['message' => 'Complex not found'], 404);
        }

        if (! $user->canManageComplex($complexe)) {
            return response()->json(['message' => 'Forbidden. You cannot manage this complex.'], 403);
        }

        return $next($request);
    }
}
