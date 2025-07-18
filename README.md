# Laravel PDGI Auth Client

A Laravel package providing a facade for OAuth2 Authorization Code Grant flow for PDGI App.

## Features

- Secure OAuth2 authorization code flow
- Automatic user creation and login
- Token management and refresh
- Configurable user model
- Ready-to-use authentication routes

## Installation

1. Install via composer:
```bash
composer require pdgi-online/auth
```

2. Publish the config file:
```bash
php artisan vendor:publish --tag=pdgi-auth-config
```

3. Add these to your .env file:
```
PDGI_CLIENT_ID=your-client-id
PDGI_CLIENT_SECRET=your-client-secret
PDGI_REDIRECT_URI="${APP_URL}/auth/callback"
PDGI_AUTH_URL=https://pdgi.online/oauth/authorize
PDGI_TOKEN_URL=https://pdgi.online/oauth/token
```

## Usage

## Full Authentication Flow

add login link
```html
<a href="{{ route('pdgi.auth') }}">Login</a>
```

1. The package will:
   - Handle the OAuth flow with PDGI auth
   - Fetch user information from the PDGI server
   - Create a local user if one doesn't exist
   - Log the user in automatically
   - Redirect to your dashboard

## Protecting Routes

Use the `pdgi.auth` middleware to protect routes:

```php
Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware('pdgi.auth');
```

## Manual Authentication

Generate authorization URL:

```php
$state = bin2hex(random_bytes(16));
$request->session()->put('oauth_state', $state);
$authUrl = PDGIAuth::getAuthorizationUrl($state);
```

Handle callback:
```php
public function callback(Request $request)
{
    if ($request->state !== $request->session()->get('oauth_state')) {
        abort(403, 'Invalid state');
    }

    $tokens = PDGIAuth::getAccessToken($request->code);
    PDGIAuth::storeTokens($tokens);

    return redirect('/home'); // Or your success route
}
```