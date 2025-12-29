<?php /** @noinspection PhpUndefinedNamespaceInspection */

/** @noinspection PhpMultipleClassDeclarationsInspection */

namespace PDGIOnline\Auth\Services;

use App\Models\User;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;
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

        return json_decode((string)$response->getBody(), true);
    }

    /**
     * Refresh access token
     * @throws GuzzleException
     */
    protected function refreshToken(string $refreshToken)
    {
        $response = $this->http->post($this->tokenUrl, [
            'form_params' => [
                'grant_type' => 'refresh_token',
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'refresh_token' => $refreshToken,
            ],
        ]);

        return json_decode((string)$response->getBody(), true);
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

    /**
     * Get cached refresh token
     * @noinspection PhpUnused
     */
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

        return json_decode((string)$response->getBody(), true);
    }

    /**
     * Find or create user from OAuth data
     */
    public function findOrCreateUser(array $userData): ?User
    {
        $user = User::where('dentist_id', $userData['dentist_id'])->first();

        if (!$user) {
            $user = new User();
            $user->name = $userData['name'];
            $user->email = $userData['email'];
            $user->avatar = $userData['avatar'] ? 'https://storage.googleapis.com/pdgi-online/certification/' . $userData['avatar'] : null;
            $user->auth_provider = 'pdgi';
            $user->dentist_id = $userData['dentist_id'];
            $user->created_at = now();
            $user->updated_at = now();
            $user->save();
        } else {
            // Update user info if there are any changes
            $updated = false;
            if ($user->name !== $userData['name']) {
                $user->name = $userData['name'];
                $updated = true;
            }
            if ($user->email !== $userData['email']) {
                $user->email = $userData['email'];
                $updated = true;
            }
            $newAvatar = $userData['avatar'] ? 'https://storage.googleapis.com/pdgi-online/certification/' . $userData['avatar'] : null;
            if ($user->avatar !== $newAvatar) {
                $user->avatar = $newAvatar;
                $updated = true;
            }
            if ($updated) {
                $user->updated_at = now();
                $user->save();
            }
        }

        return $user;
    }

    /**
     * Complete the auth flow
     * @throws GuzzleException
     * @noinspection PhpParamsInspection
     */
    public function completeAuthFlow(string $code): ?User
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
     * @noinspection PhpUnused
     */
    public function getUserMemberships()
    {
        $accessToken = $this->getSessionAccessToken();
        if (!$accessToken) {
            return null;
        }

        $uri = config('pdgi-auth.client.base_url') . '/api/memberships';
        try {
            $response = $this->http->get($uri, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Accept' => 'application/json',
                ],
            ]);
        } catch (GuzzleException $e) {
            if ($e->getCode() === 401) {
                // Token might be expired, try to refresh
                $refreshToken = $this->getSessionRefreshToken();
                if ($refreshToken) {
                    $newTokens = $this->refreshToken($refreshToken);
                    $this->storeTokens($newTokens);
                    // Retry the request with new access token
                    return $this->getUserMemberships();
                }
                throw $e;
            }
            throw $e;
        }

        $json = json_decode($response->getBody(), true);
        if (!$json || !isset($json['data'])) {
            throw new BadResponseException('Invalid response from PDGI server', Request('GET', $uri ), $response);
        }
        return $json['data'];
    }
}
