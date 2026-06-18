<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * Returning null for JSON / API requests ensures they receive a 401 instead
     * of attempting to redirect to a named `login` route that may not exist.
     */
    protected function redirectTo($request)
    {
        if ($request->expectsJson()) {
            return null;
        }

        // For non-API requests, redirect to the SPA login path rather than a
        // potentially undefined named route.
        return '/auth/login';
    }
}
