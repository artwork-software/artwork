<?php

namespace Artwork\Modules\ExternalUserManagement\Api;

use Artwork\Modules\ExternalUserManagement\Models\ExternalUserSource;
use Artwork\Modules\ExternalUserManagement\Socialite\GenericOidcProvider;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;

class OidcApi implements ExternalUserManagementApi
{
    /**
     * Baut die Redirect-Response zum Identity Provider (Authorization-Code-Flow).
     */
    public function redirect(ExternalUserSource $source): RedirectResponse
    {
        return $this->provider($source)->redirect();
    }

    /**
     * Tauscht den Authorization-Code gegen ein Token, holt die Claims vom
     * Userinfo-Endpoint und normalisiert sie auf dieselbe Shape wie
     * {@see \Artwork\Modules\ExternalUserManagement\Api\LdapApi::fetchUsers()}.
     *
     * @return array{identifier: string, email: string|null, first_name: string, last_name: string, groups: array<int, string>, email_verified: bool, meta_data: array<string, mixed>}
     */
    public function userFromCallback(ExternalUserSource $source): array
    {
        $config = $source->config ?? [];

        /** @var SocialiteUser $oidcUser */
        $oidcUser = $this->provider($source)->user();

        return $this->normalizeClaims(
            $oidcUser->getRaw(),
            $oidcUser->getId(),
            $oidcUser->getEmail(),
            $oidcUser->getName(),
            $config['identifier_attribute'] ?? 'sub',
            $config['groups_claim'] ?? 'groups'
        );
    }

    /**
     * Normalisiert rohe OIDC-Claims auf dieselbe Shape wie
     * {@see \Artwork\Modules\ExternalUserManagement\Api\LdapApi::fetchUsers()}.
     * Pure Funktion (ohne I/O) — direkt unit-testbar.
     *
     * @param array<string, mixed> $raw
     * @return array{identifier: string, email: string|null, first_name: string, last_name: string, groups: array<int, string>, email_verified: bool, meta_data: array<string, mixed>}
     */
    public function normalizeClaims(
        array $raw,
        ?string $fallbackId,
        ?string $email,
        ?string $name,
        string $identifierAttribute = 'sub',
        string $groupsClaim = 'groups'
    ): array {
        $identifier = $raw[$identifierAttribute] ?? $fallbackId;

        if ($identifier === null || $identifier === '') {
            throw new \RuntimeException("OIDC identifier claim '{$identifierAttribute}' is missing");
        }

        $groups = $raw[$groupsClaim] ?? [];
        if (!is_array($groups)) {
            $groups = array_filter([$groups]);
        }
        $groups = array_values(array_map('strval', $groups));

        return [
            'identifier' => (string) $identifier,
            'email' => $email,
            'first_name' => $raw['given_name'] ?? '',
            'last_name' => $raw['family_name'] ?? '',
            'groups' => $groups,
            'email_verified' => (bool) ($raw['email_verified'] ?? false),
            'meta_data' => [
                'sub' => $raw['sub'] ?? null,
                'preferred_username' => $raw['preferred_username'] ?? null,
                'name' => $name,
                'email_verified' => $raw['email_verified'] ?? null,
                'groups' => $groups,
            ],
        ];
    }

    /**
     * Prüft, ob das OIDC-Discovery-Dokument erreichbar ist und die
     * erforderlichen Endpoints enthält.
     */
    public function testConnection(ExternalUserSource $source): bool
    {
        $discovery = $this->resolveDiscovery($source, useCache: false);

        foreach (['authorization_endpoint', 'token_endpoint', 'userinfo_endpoint'] as $key) {
            if (empty($discovery[$key])) {
                throw new \RuntimeException("OIDC discovery document is missing '{$key}'");
            }
        }

        return true;
    }

    private function provider(ExternalUserSource $source): GenericOidcProvider
    {
        $config = $source->config ?? [];
        $discovery = $this->resolveDiscovery($source);

        /** @var GenericOidcProvider $provider */
        $provider = Socialite::buildProvider(GenericOidcProvider::class, [
            'client_id' => $config['client_id'] ?? '',
            'client_secret' => $config['client_secret'] ?? '',
            'redirect' => route('auth.oidc.callback', ['externalUserSource' => $source->id]),
            'scopes' => $config['scopes'] ?? ['openid', 'profile', 'email'],
        ]);

        return $provider->setOpenidConfig($discovery)->enablePKCE();
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveDiscovery(ExternalUserSource $source, bool $useCache = true): array
    {
        $url = $this->discoveryUrl($source->config ?? []);
        $fetch = fn (): array => $this->fetchDiscovery($url);

        if (!$useCache) {
            return $fetch();
        }

        return Cache::remember('oidc_discovery_' . md5($url), now()->addHour(), $fetch);
    }

    /**
     * @param array<string, mixed> $config
     */
    private function discoveryUrl(array $config): string
    {
        if (!empty($config['discovery_url'])) {
            return $config['discovery_url'];
        }

        return rtrim((string) ($config['issuer'] ?? ''), '/') . '/.well-known/openid-configuration';
    }

    /**
     * @return array<string, mixed>
     */
    private function fetchDiscovery(string $url): array
    {
        return Http::acceptJson()
            ->connectTimeout(5)
            ->timeout(10)
            ->retry(2, 200)
            ->get($url)
            ->throw()
            ->json() ?? [];
    }
}
