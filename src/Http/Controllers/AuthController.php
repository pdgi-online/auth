<?php

namespace PDGIOnline\Auth\Http\Controllers;

use Illuminate\Http\Request;
use PDGIOnline\Auth\Facades\PDGIAuth;
use PDGIOnline\Auth\Http\Controllers\Controller;
use Random\RandomException;

class AuthController extends Controller
{
    public function redirect(Request $request)
    {
        try {
            $state = bin2hex(random_bytes(16));
        } catch (RandomException $e) {
            abort(500, 'Could not generate state parameter');
        }
        $request->session()->put('oauth_state', $state);

        return redirect()->away(
            PDGIAuth::getAuthorizationUrl($state)
        );
    }

    public function callback(Request $request)
    {
        // Validate state
        if ($request->state !== $request->session()->pull('oauth_state')) {
            abort(403, 'Invalid state parameter');
        }

        try {
            // Complete auth flow (gets tokens, fetches user, logs in)
            PDGIAuth::completeAuthFlow($request->code);

            return redirect()->intended(route('dashboard'));
        } catch (\Exception $e) {
            return redirect()->route('login')
                ->with('error', 'Authentication failed: ' . $e->getMessage());
        }
    }
}
