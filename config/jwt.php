<?php

use Tymon\JWTAuth\Providers\Auth\Illuminate;
use Tymon\JWTAuth\Providers\JWT\Lcobucci;

/*
|--------------------------------------------------------------------------
| JWT Configuration — PlaySpace
|--------------------------------------------------------------------------
|
| After running: php artisan jwt:secret
| the JWT_SECRET will be auto-added to your .env file.
|
*/

return [
    'secret' => env('JWT_SECRET'),

    'keys' => [
        'public' => env('JWT_PUBLIC_KEY'),
        'private' => env('JWT_PRIVATE_KEY'),
        'passphrase' => env('JWT_PASSPHRASE'),
    ],

    /*
    |  Token lifetime
    |  60 = 60 minutes (1 hour)
    */
    'ttl' => env('JWT_TTL', 60),

    /*
    |  Refresh token lifetime
    |  20160 = 2 weeks
    */
    'refresh_ttl' => env('JWT_REFRESH_TTL', 20160),

    'algo' => env('JWT_ALGO', 'HS256'),

    'required_claims' => [
        'iss',
        'iat',
        'exp',
        'nbf',
        'sub',
        'jti',
    ],

    'persistent_claims' => [
        'role',
        'first_name',
        'email',
    ],

    'lock_subject' => true,

    'leeway' => env('JWT_LEEWAY', 0),

    'blacklist_enabled' => env('JWT_BLACKLIST_ENABLED', true),

    'blacklist_grace_period' => env('JWT_BLACKLIST_GRACE_PERIOD', 0),

    'decrypt_cookies' => false,

    'providers' => [
        'jwt' => Lcobucci::class,
        'auth' => Illuminate::class,
        'storage' => Tymon\JWTAuth\Providers\Storage\Illuminate::class,
    ],
];
