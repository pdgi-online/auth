<?php
namespace PDGIOnline\PDGIAuthClient\Http\Controllers;

use Illuminate\Http\Request;
use PDGIOnline\PDGIAuthClient\Facades\PDGIAuth;
use App\Http\Controllers\Controller;

class AuthController extends Controller
{
    public function redirect(Request $request)
    {
        $state = bin2hex(random_bytes(16));
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
            $user = PDGIAuth::completeAuthFlow($request->code);
            
            return redirect()->intended('/dashboard')
                ->with('success', 'Logged in successfully!');
                
        } catch (\Exception $e) {
            return redirect()->route('login')
                ->with('error', 'Authentication failed: ' . $e->getMessage());
        }
    }
}