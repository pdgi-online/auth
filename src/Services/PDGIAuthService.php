<?php

namespace PDGIOnline\Auth\Services;

use App\Models\User;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class PDGIAuthService
{
    protected Client $http;
    protected string $clientId;
    protected string $clientSecret;
    protected string $redirectUri;
    protected string $authUrl;
    protected string $tokenUrl;

    public function __construct()
    {
        $this->http = new Client();
        $this->clientId = config('pdgi-auth.client.id');
        $this->clientSecret = config('pdgi-auth.client.secret');
        $this->redirectUri = config('pdgi-auth.client.redirect_uri');
        $this->authUrl = config('pdgi-auth.client.base_url') . '/oauth/authorize';
        $this->tokenUrl = config('pdgi-auth.client.base_url') . '/oauth/token';
    }

    /**
     * Generate the authorization URL
     */
    public function getAuthorizationUrl(string $state): string
    {
        $query = http_build_query([
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'response_type' => 'code',
            'scope' => '',
            'state' => $state,
        ]);

        return $this->authUrl . '?' . $query;
    }

    /**
     * Exchange authorization code for access token
     * @throws GuzzleException
     */
    public function getAccessToken(string $code)
    {
        $response = $this->http->post($this->tokenUrl, [
            'form_params' => [
                'grant_type' => 'authorization_code',
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'redirect_uri' => $this->redirectUri,
                'code' => $code,
            ],
        ]);

        return json_decode((string) $response->getBody(), true);
    }

    /**
     * Refresh access token
     * @throws GuzzleException
     */
    public function refreshToken(string $refreshToken)
    {
        $response = $this->http->post($this->tokenUrl, [
            'form_params' => [
                'grant_type' => 'refresh_token',
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'refresh_token' => $refreshToken,
            ],
        ]);

        return json_decode((string) $response->getBody(), true);
    }

    /**
     * Store tokens in session
     */
    public function storeTokens(array $tokens): void
    {
        // store access token in session
        Session::put('pdgi_access_token', $tokens['access_token']);
        Session::put('pdgi_refresh_token', $tokens['refresh_token']);
    }

    /**
     * Get cached access token
     */
    public function getSessionAccessToken()
    {
        return Session::get('pdgi_access_token');
    }

    public function getSessionRefreshToken()
    {
        return Session::get('pdgi_refresh_token');
    }

    /**
     * Get user info from PDGI server
     * @throws GuzzleException
     */
    public function getUserInfo(string $accessToken)
    {
        $userUrl = config('pdgi-auth.client.base_url') . '/api/user';

        $response = $this->http->get($userUrl, [
            'headers' => [
                'Authorization' => 'Bearer ' . $accessToken,
                'Accept' => 'application/json',
            ],
        ]);

        return json_decode((string) $response->getBody(), true);
    }

    /**
     * Find or create user from OAuth data
     */
    public function findOrCreateUser(array $userData)
    {
        $user = User::where('dentist_id', $userData['dentist_id'])->first();

        if (!$user) {
            $user = new User();
            $user->name = $userData['name'];
            $user->email = $userData['email'];
            $user->auth_provider = 'pdgi';
            $user->dentist_id = $userData['dentist_id'];
            $user->created_at = now();
            $user->updated_at = now();
            $user->save();
        }

        return $user;
    }

    /**
     * Complete the auth flow
     * @throws GuzzleException
     */
    public function completeAuthFlow(string $code)
    {
        // Get tokens
        $tokens = $this->getAccessToken($code);
        $this->storeTokens($tokens);

        // Get user info
        $userInfo = $this->getUserInfo($tokens['access_token']);

        // Find or create user
        $user = $this->findOrCreateUser($userInfo);

        // Login user
        Auth::login($user, true);

        return $user;
    }

    /**
     * @throws GuzzleException
     */
    public function getUserMemberships()
    {
        $accessToken = $this->getSessionAccessToken();
        if (!$accessToken) {
            return null;
        }

        $response = $this->http->get(config('pdgi-auth.client.base_url') . '/api/memberships', [
            'headers' => [
                'Authorization' => 'Bearer ' . $accessToken,
                'Accept' => 'application/json',
            ],
        ]);

        $json = json_decode($response->getBody(), true);
        return $json['data'] ?? null;
    }
}
