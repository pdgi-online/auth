<?php

return [
    'client' => [
        'id' => env('PDGI_CLIENT_ID'),
        'secret' => env('PDGI_CLIENT_SECRET'),
        'redirect_uri' => env('PDGI_REDIRECT_URI', config('app.url') . '/auth/pdgi/callback'),
        'auth_url' => env('PDGI_AUTH_URL', 'https://pdgi.online/oauth/authorize'),
        'token_url' => env('PDGI_TOKEN_URL', 'https://pdgi.online/oauth/token'),
        'user_url' => env('PDGI_USER_URL', 'https://pdgi.online/api/user'),
    ],
];
