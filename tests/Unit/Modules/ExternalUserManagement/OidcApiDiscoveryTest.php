<?php

namespace Tests\Unit\Modules\ExternalUserManagement;

use Artwork\Modules\ExternalUserManagement\Api\OidcApi;
use Artwork\Modules\ExternalUserManagement\Models\ExternalUserSource;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

final class OidcApiDiscoveryTest extends TestCase
{
    private function source(array $config): ExternalUserSource
    {
        $source = new ExternalUserSource();
        $source->type = 'identity_provider';
        $source->config = $config;

        return $source;
    }

    #[Test]
    public function test_connection_succeeds_when_discovery_document_is_complete(): void
    {
        Http::fake([
            'https://idp.example.com/.well-known/openid-configuration' => Http::response([
                'authorization_endpoint' => 'https://idp.example.com/authorize',
                'token_endpoint' => 'https://idp.example.com/token',
                'userinfo_endpoint' => 'https://idp.example.com/userinfo',
            ]),
        ]);

        $source = $this->source([
            'discovery_url' => 'https://idp.example.com/.well-known/openid-configuration',
        ]);

        $this->assertTrue(app(OidcApi::class)->testConnection($source));
    }

    #[Test]
    public function test_connection_throws_when_a_required_endpoint_is_missing(): void
    {
        Http::fake([
            '*' => Http::response([
                'authorization_endpoint' => 'https://idp.example.com/authorize',
                'token_endpoint' => 'https://idp.example.com/token',
                // userinfo_endpoint fehlt absichtlich
            ]),
        ]);

        $source = $this->source([
            'discovery_url' => 'https://idp.example.com/.well-known/openid-configuration',
        ]);

        $this->expectException(RuntimeException::class);

        app(OidcApi::class)->testConnection($source);
    }

    #[Test]
    public function it_derives_the_discovery_url_from_the_issuer(): void
    {
        Http::fake([
            'https://issuer.example.com/.well-known/openid-configuration' => Http::response([
                'authorization_endpoint' => 'https://issuer.example.com/authorize',
                'token_endpoint' => 'https://issuer.example.com/token',
                'userinfo_endpoint' => 'https://issuer.example.com/userinfo',
            ]),
        ]);

        $source = $this->source(['issuer' => 'https://issuer.example.com']);

        $this->assertTrue(app(OidcApi::class)->testConnection($source));
        Http::assertSent(
            fn ($request) => $request->url() === 'https://issuer.example.com/.well-known/openid-configuration'
        );
    }
}
