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

2. Migrate database for PDGI Auth

```bash
php artisan migrate
```

3. Add these to your .env file:

```
PDGI_CLIENT_ID=your-client-id
PDGI_CLIENT_SECRET=your-client-secret
PDGI_REDIRECT_URI="${APP_URL}/auth/callback"
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
