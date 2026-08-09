<?php

return [

    'guard' => 'web',
    'passwords' => 'users',

    'username' => 'username',
    'email' => 'email',
    'lowercase_usernames' => true,

    'home' => '/dashboard',

    'prefix' => '',
    'domain' => null,

    'middleware' => ['web'],
    'auth_middleware' => 'auth',

    'limiters' => [
        'login' => 'login',
    ],

    'views' => true,

    'passkeys' => [
        'relying_party_id' => parse_url(config('app.url'), PHP_URL_HOST),
        'allowed_origins' => [config('app.url')],
        'timeout' => 60000,
    ],

    'features' => [
        // Tahap 1: login/logout only — registration, password reset, 2FA, passkeys disabled
    ],

];
