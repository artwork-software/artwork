<?php

namespace Tests\Unit\Modules\ExternalUserManagement;

use Artwork\Modules\ExternalUserManagement\Support\OidcProviderPreset;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class OidcProviderPresetTest extends TestCase
{
    #[Test]
    public function google_preset_prefills_discovery_issuer_and_scopes(): void
    {
        $config = OidcProviderPreset::normalize([
            'provider_preset' => 'google',
            'client_id' => 'abc',
            'client_secret' => 'shh',
        ]);

        $this->assertSame('https://accounts.google.com/.well-known/openid-configuration', $config['discovery_url']);
        $this->assertSame('https://accounts.google.com', $config['issuer']);
        $this->assertSame('sub', $config['identifier_attribute']);
        $this->assertSame(['openid', 'profile', 'email'], $config['scopes']);
        $this->assertArrayNotHasKey('tenant_id', $config);
    }

    #[Test]
    public function microsoft_preset_builds_authority_from_tenant(): void
    {
        $config = OidcProviderPreset::normalize([
            'provider_preset' => 'microsoft',
            'tenant_id' => 'my-tenant',
            'client_id' => 'abc',
        ]);

        $this->assertSame(
            'https://login.microsoftonline.com/my-tenant/v2.0/.well-known/openid-configuration',
            $config['discovery_url']
        );
        $this->assertSame('https://login.microsoftonline.com/my-tenant/v2.0', $config['issuer']);
    }

    #[Test]
    public function custom_preset_leaves_the_provided_config_untouched(): void
    {
        $config = OidcProviderPreset::normalize([
            'provider_preset' => 'custom',
            'discovery_url' => 'https://idp.example.com/.well-known/openid-configuration',
            'identifier_attribute' => 'oid',
        ]);

        $this->assertSame('https://idp.example.com/.well-known/openid-configuration', $config['discovery_url']);
        $this->assertSame('oid', $config['identifier_attribute']);
    }

    #[Test]
    public function missing_preset_defaults_to_custom(): void
    {
        $config = OidcProviderPreset::normalize(['discovery_url' => 'https://idp.example.com']);

        $this->assertSame('custom', $config['provider_preset']);
    }
}
