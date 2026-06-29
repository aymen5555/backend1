<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes — PlaySpace API backend
|--------------------------------------------------------------------------
|
| Angular runs separately on http://localhost:4200 in development.
| This server (port 8000) is the Laravel API only.
|
*/

Route::get('/', function () {
    return response()->json([
        'name' => 'PlaySpace API',
        'version' => 'Sprint 1',
        'docs' => [
            'health' => url('/up'),
            'register' => url('/api/auth/register'),
            'login' => url('/api/auth/login'),
        ],
        'frontend' => env('FRONTEND_URL', 'http://localhost:4200'),
    ]);
});

// Provide a simple named login route so middleware that calls `route('login')`
// does not throw an exception. Redirects to the SPA login path.
Route::get('/login', function () {
    return redirect(env('FRONTEND_URL', 'http://localhost:4200').'/auth/login');
})->name('login');
