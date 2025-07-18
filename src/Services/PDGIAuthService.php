<?php

namespace PDGIOnline\PDGIAuthClient\Services;

use GuzzleHttp\Client;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;

class PDGIAuthService
{
    protected $http;
    protected $clientId;
    protected $clientSecret;
    protected $redirectUri;
    protected $authUrl;
    protected $tokenUrl;

    public function __construct()
    {
        $this->http = new Client();
        $this->clientId = config('pdgi-auth.client.id');
        $this->clientSecret = config('pdgi-auth.client.secret');
        $this->redirectUri = config('pdgi-auth.client.redirect_uri');
        $this->authUrl = config('pdgi-auth.client.auth_url');
        $this->tokenUrl = config('pdgi-auth.client.token_url');
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
     * Store tokens in cache
     */
    public function storeTokens(array $tokens)
    {
        Cache::put('pdgi_access_token', $tokens['access_token'], $tokens['expires_in']);
        Cache::put('pdgi_refresh_token', $tokens['refresh_token'], now()->addDays(30));
    }

    /**
     * Get cached access token
     */
    public function getCachedAccessToken()
    {
        return Cache::get('pdgi_access_token');
    }

    /**
     * Get user info from PDGI server
     */
    public function getUserInfo(string $accessToken)
    {
        $userUrl = config('pdgi-auth.client.user_url');

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
            $user = User::create([
                'dentist_id' => $userData['dentist_id'],
                'auth_provider' => 'pdgi',
                'name' => $userData['name'] ?? '',
                'email' => $userData['email'],
            ]);
        }

        return $user;
    }

    /**
     * Complete the auth flow
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
}
