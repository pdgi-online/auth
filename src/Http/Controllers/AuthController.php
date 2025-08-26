<?php

namespace PDGIOnline\Auth\Http\Controllers;

use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Http\Request;
use PDGIOnline\Auth\Facades\PDGIAuth;
use Random\RandomException;

class AuthController extends Controller
{
    public function redirect(Request $request)
    {
        try {
            $state = bin2hex(random_bytes(16));
        } catch (RandomException) {
            abort(500, 'Could not generate state parameter');
        }
        $request->session()->put('oauth_state', $state);

        return redirect()->away(
            PDGIAuth::getAuthorizationUrl($state)
        );
    }

    /**
     * @throws GuzzleException
     */
    public function callback(Request $request)
    {
        // Validate state
        if ($request->state !== $request->session()->pull('oauth_state')) {
            abort(403, 'Invalid state parameter');
        }

        PDGIAuth::completeAuthFlow($request->code);

        return redirect()->intended(route('dashboard'));
    }
}
