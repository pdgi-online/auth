<?php

return [
    'client' => [
        'id' => env('PDGI_CLIENT_ID'),
        'secret' => env('PDGI_CLIENT_SECRET'),
        'redirect_uri' => env('PDGI_REDIRECT_URI', config('app.url') . '/auth/pdgi/callback'),
        'base_url' => env('PDGI_BASE_URL', 'https://pdgi.online'),
    ],
];
