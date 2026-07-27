<?php

namespace Artwork\Modules\ExternalUserManagement\Support;

/**
 * Normalisiert die drei Provider-Presets (Google / Microsoft / Custom) auf
 * dieselbe Konfigurationsstruktur wie Custom. Es gibt keinen zweiten Codepfad,
 * nur unterschiedliche Defaults – die Runtime kennt anschließend ausschließlich
 * discovery_url / issuer / scopes / identifier_attribute / groups_claim.
 */
class OidcProviderPreset
{
    public const GOOGLE = 'google';
    public const MICROSOFT = 'microsoft';
    public const CUSTOM = 'custom';

    /**
     * @param array<string, mixed> $config
     * @return array<string, mixed>
     */
    public static function normalize(array $config): array
    {
        $preset = $config['provider_preset'] ?? self::CUSTOM;

        return match ($preset) {
            self::GOOGLE => self::applyGoogle($config),
            self::MICROSOFT => self::applyMicrosoft($config),
            default => self::applyCustom($config),
        };
    }

    /**
     * @param array<string, mixed> $config
     * @return array<string, mixed>
     */
    private static function applyGoogle(array $config): array
    {
        $config['provider_preset'] = self::GOOGLE;
        $config['discovery_url'] = 'https://accounts.google.com/.well-known/openid-configuration';
        $config['issuer'] = 'https://accounts.google.com';
        $config['identifier_attribute'] = 'sub';
        $config['groups_claim'] = $config['groups_claim'] ?? 'groups';
        $config['scopes'] = self::scopesOrDefault($config);

        unset($config['tenant_id']);

        return $config;
    }

    /**
     * @param array<string, mixed> $config
     * @return array<string, mixed>
     */
    private static function applyMicrosoft(array $config): array
    {
        $tenant = trim((string) ($config['tenant_id'] ?? ''));

        $config['provider_preset'] = self::MICROSOFT;
        $config['tenant_id'] = $tenant;
        $config['discovery_url'] = "https://login.microsoftonline.com/{$tenant}/v2.0/.well-known/openid-configuration";
        $config['issuer'] = "https://login.microsoftonline.com/{$tenant}/v2.0";
        $config['identifier_attribute'] = 'sub';
        $config['groups_claim'] = $config['groups_claim'] ?? 'groups';
        $config['scopes'] = self::scopesOrDefault($config);

        return $config;
    }

    /**
     * @param array<string, mixed> $config
     * @return array<string, mixed>
     */
    private static function applyCustom(array $config): array
    {
        $config['provider_preset'] = self::CUSTOM;
        unset($config['tenant_id']);

        return $config;
    }

    /**
     * @param array<string, mixed> $config
     * @return array<int, string>
     */
    private static function scopesOrDefault(array $config): array
    {
        $scopes = $config['scopes'] ?? [];

        if (!is_array($scopes) || $scopes === []) {
            return ['openid', 'profile', 'email'];
        }

        return array_values($scopes);
    }
}
