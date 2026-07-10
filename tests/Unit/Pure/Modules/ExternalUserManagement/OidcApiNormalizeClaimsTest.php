<?php

namespace Tests\Unit\Pure\Modules\ExternalUserManagement;

use Artwork\Modules\ExternalUserManagement\Api\OidcApi;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

final class OidcApiNormalizeClaimsTest extends TestCase
{
    private function api(): OidcApi
    {
        return new OidcApi();
    }

    #[Test]
    public function it_maps_standard_oidc_claims_to_the_ldap_shape(): void
    {
        $raw = [
            'sub' => 'abc-123',
            'email' => 'jane@example.com',
            'email_verified' => true,
            'given_name' => 'Jane',
            'family_name' => 'Doe',
            'preferred_username' => 'jane',
            'groups' => ['admins', 'editors'],
        ];

        $result = $this->api()->normalizeClaims($raw, 'abc-123', 'jane@example.com', 'Jane Doe');

        $this->assertSame('abc-123', $result['identifier']);
        $this->assertSame('jane@example.com', $result['email']);
        $this->assertSame('Jane', $result['first_name']);
        $this->assertSame('Doe', $result['last_name']);
        $this->assertSame(['admins', 'editors'], $result['groups']);
        $this->assertTrue($result['email_verified']);
        $this->assertSame('jane', $result['meta_data']['preferred_username']);
        $this->assertSame(['admins', 'editors'], $result['meta_data']['groups']);
    }

    #[Test]
    public function it_uses_a_custom_identifier_claim_when_configured(): void
    {
        $raw = ['sub' => 'sub-1', 'oid' => 'azure-oid-9', 'email' => 'x@e.com'];

        $result = $this->api()->normalizeClaims($raw, 'sub-1', 'x@e.com', null, 'oid');

        $this->assertSame('azure-oid-9', $result['identifier']);
    }

    #[Test]
    public function it_reads_groups_from_a_custom_claim_and_normalizes_scalars_to_arrays(): void
    {
        $raw = ['sub' => 's', 'roles' => 'single-group'];

        $result = $this->api()->normalizeClaims($raw, 's', null, null, 'sub', 'roles');

        $this->assertSame(['single-group'], $result['groups']);
    }

    #[Test]
    public function it_defaults_groups_to_empty_array_when_claim_is_missing(): void
    {
        $result = $this->api()->normalizeClaims(['sub' => 's'], 's', null, null);

        $this->assertSame([], $result['groups']);
        $this->assertFalse($result['email_verified']);
    }

    #[Test]
    public function it_falls_back_to_the_provider_id_when_identifier_claim_absent(): void
    {
        $result = $this->api()->normalizeClaims(['email' => 'a@b.com'], 'fallback-id', 'a@b.com', null);

        $this->assertSame('fallback-id', $result['identifier']);
    }

    #[Test]
    public function it_throws_when_no_identifier_can_be_resolved(): void
    {
        $this->expectException(RuntimeException::class);

        $this->api()->normalizeClaims(['email' => 'a@b.com'], null, 'a@b.com', null);
    }
}
