<?php

namespace App\Http\Middleware;

use App\Models\Complexe;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EnsureGerantOwnsComplexe
{
    /**
     * Handle an incoming request.
     * Checks route params and request input for a complexe id and enforces ownership for gerant users.
     */
    public function handle(Request $request, Closure $next)
    {
        $user = auth('api')->user();

        // If no authenticated user or user is super_admin, allow
        if (! $user || $user->role === 'super_admin') {
            return $next($request);
        }

        // Only enforce for gerant role
        if ($user->role !== 'gerant') {
            return $next($request);
        }

        $complexeId = $this->resolveComplexeId($request);

        // If no explicit complexe scope is present, let the controller decide.
        // This avoids blocking gerant endpoints that scope themselves by ownership or business rules.
        if ($complexeId === null) {
            Log::debug('EnsureGerantOwnsComplexe: no explicit complexe scope; allowing request', [
                'user_id' => $user->id ?? null,
                'route' => $request->path(),
                'method' => $request->method(),
            ]);

            return $next($request);
        }

        $complexe = Complexe::find($complexeId);
        if (! $complexe) {
            Log::warning('EnsureGerantOwnsComplexe: complexe not found', [
                'user_id' => $user->id ?? null,
                'route' => $request->path(),
                'method' => $request->method(),
                'complexe_id' => $complexeId,
            ]);
            return response()->json(['success' => false, 'message' => 'Complexe not found.'], 404);
        }

        if ($complexe->owner_id !== $user->id) {
            Log::warning('EnsureGerantOwnsComplexe: forbidden for gerant', [
                'user_id' => $user->id ?? null,
                'route' => $request->path(),
                'method' => $request->method(),
                'complexe_id' => $complexeId,
                'complexe_owner_id' => $complexe->owner_id ?? null,
            ]);
            return response()->json(['success' => false, 'message' => 'You are not authorized for this complexe.'], 403);
        }

        Log::debug('EnsureGerantOwnsComplexe: ownership verified', [
            'user_id' => $user->id ?? null,
            'route' => $request->path(),
            'method' => $request->method(),
            'complexe_id' => $complexeId,
        ]);

        return $next($request);
    }

    private function resolveComplexeId(Request $request): ?int
    {
        $route = $request->route();

        if ($route) {
            $param = $route->parameter('complexe') ?? $route->parameter('complexe_id');
            if ($param !== null) {
                return $this->normalizeComplexeId($param);
            }

            foreach ($route->parameters() as $value) {
                if (! is_object($value)) {
                    continue;
                }

                if (isset($value->complexe_id)) {
                    return $this->normalizeComplexeId($value->complexe_id);
                }

                if (isset($value->complexe) && is_object($value->complexe) && isset($value->complexe->id)) {
                    return $this->normalizeComplexeId($value->complexe->id);
                }
            }
        }

        foreach (['complexe_id', 'complexeId', 'complexe'] as $key) {
            $value = $request->input($key) ?? $request->query($key);
            if ($value !== null) {
                return $this->normalizeComplexeId($value);
            }
        }

        return null;
    }

    private function normalizeComplexeId(mixed $value): ?int
    {
        if (is_int($value) || is_numeric($value)) {
            return (int) $value;
        }

        if (is_object($value) && isset($value->id) && is_numeric($value->id)) {
            return (int) $value->id;
        }

        return null;
    }
}
