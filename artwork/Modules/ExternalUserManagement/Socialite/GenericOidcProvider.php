<?php

namespace Artwork\Modules\ExternalUserManagement\Socialite;

use GuzzleHttp\RequestOptions;
use Laravel\Socialite\Two\AbstractProvider;
use Laravel\Socialite\Two\User;

/**
 * Generischer OpenID-Connect Provider für Laravel Socialite.
 *
 * Nutzt die Authorization-Code-Flow-Basis aus {@see AbstractProvider}
 * (State, PKCE, Token-Exchange) und bekommt die konkreten Endpoints
 * (authorization/token/userinfo) zur Laufzeit aus dem OIDC-Discovery-Dokument
 * (.well-known/openid-configuration) via {@see setOpenidConfig()} injiziert.
 */
class GenericOidcProvider extends AbstractProvider
{
    /**
     * OIDC nutzt Leerzeichen als Scope-Trenner (statt Komma).
     *
     * @var string
     */
    protected $scopeSeparator = ' ';

    /**
     * Auflösung des Discovery-Dokuments: authorization_endpoint,
     * token_endpoint, userinfo_endpoint, jwks_uri, issuer.
     *
     * @var array<string, mixed>
     */
    protected array $openidConfig = [];

    /**
     * Setzt die aus dem Discovery-Dokument aufgelösten Endpoints.
     *
     * @param array<string, mixed> $config
     */
    public function setOpenidConfig(array $config): static
    {
        $this->openidConfig = $config;

        return $this;
    }

    protected function getAuthUrl($state): string
    {
        return $this->buildAuthUrlFromBase($this->openidConfig['authorization_endpoint'], $state);
    }

    protected function getTokenUrl(): string
    {
        return $this->openidConfig['token_endpoint'];
    }

    /**
     * @param string $token
     * @return array<string, mixed>
     */
    protected function getUserByToken($token): array
    {
        $response = $this->getHttpClient()->get($this->openidConfig['userinfo_endpoint'], [
            RequestOptions::HEADERS => [
                'Authorization' => 'Bearer ' . $token,
                'Accept' => 'application/json',
            ],
        ]);

        return json_decode((string) $response->getBody(), true) ?? [];
    }

    /**
     * @param array<string, mixed> $user
     */
    protected function mapUserToObject(array $user): User
    {
        return (new User())->setRaw($user)->map([
            'id' => $user['sub'] ?? null,
            'nickname' => $user['preferred_username'] ?? null,
            'name' => $user['name'] ?? trim(($user['given_name'] ?? '') . ' ' . ($user['family_name'] ?? '')) ?: null,
            'email' => $user['email'] ?? null,
            'avatar' => $user['picture'] ?? null,
        ]);
    }
}
