<?php

namespace Tests\Unit;

use Artwork\Core\Validation\Rules\PublicUrlRule;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Sicherheits-Audit 21.09.2026 (E): SSRF-Schutz für serverseitig aufgerufene URLs (Webhooks, OIDC-Discovery).
 * Der Resolver wird pro Test ersetzt — kein echtes DNS.
 */
final class PublicUrlRuleTest extends TestCase
{
    protected function tearDown(): void
    {
        PublicUrlRule::resolveUsing(null);

        parent::tearDown();
    }

    /**
     * @param array<int, string> $resolvedIps
     * @return array<int, string> Fehlermeldungen (leer = gültig)
     */
    private function failures(string $url, array $resolvedIps = ['93.184.216.34'], array $schemes = ['http', 'https']): array
    {
        PublicUrlRule::resolveUsing(static fn (string $host): array => $resolvedIps);

        $messages = [];
        (new PublicUrlRule($schemes))->validate('url', $url, static function (string $message) use (&$messages): void {
            $messages[] = $message;
        });

        return $messages;
    }

    #[Test]
    public function public_hostname_passes(): void
    {
        $this->assertSame([], $this->failures('https://hooks.example.com/incoming'));
    }

    #[Test]
    public function public_ipv6_passes(): void
    {
        $this->assertSame([], $this->failures('https://[2606:4700::6810:84e5]/x', ['2606:4700::6810:84e5']));
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function privateLiteralUrls(): array
    {
        return [
            'loopback' => ['http://127.0.0.1/'],
            'loopback other' => ['http://127.5.6.7:8080/admin'],
            'unspecified' => ['http://0.0.0.0/'],
            'rfc1918 10' => ['https://10.0.0.5/'],
            'rfc1918 172' => ['https://172.16.44.1/'],
            'rfc1918 172 upper bound' => ['https://172.31.255.254/'],
            'rfc1918 192' => ['https://192.168.1.1/'],
            'link-local / metadata' => ['http://169.254.169.254/latest/meta-data/'],
            'cgnat' => ['http://100.64.1.1/'],
            'multicast' => ['http://224.0.0.1/'],
            'broadcast' => ['http://255.255.255.255/'],
            'ipv6 loopback' => ['http://[::1]/'],
            'ipv6 unspecified' => ['http://[::]/'],
            'ipv6 ula' => ['http://[fd12:3456::1]/'],
            'ipv6 link-local' => ['http://[fe80::1]/'],
            'ipv6 mapped loopback' => ['http://[::ffff:127.0.0.1]/'],
            'ipv6 nat64 rfc1918' => ['http://[64:ff9b::10.0.0.1]/'],
            'localhost' => ['http://localhost/'],
            'localhost subdomain' => ['http://foo.localhost/'],
            'decimal ip' => ['http://2130706433/'],
            'hex ip' => ['http://0x7f000001/'],
            'short dotted ip' => ['http://127.1/'],
        ];
    }

    #[Test]
    #[DataProvider('privateLiteralUrls')]
    public function private_or_special_addresses_are_rejected(string $url): void
    {
        $this->assertNotSame([], $this->failures($url), $url . ' should be rejected');
    }

    #[Test]
    public function hostname_resolving_to_private_address_is_rejected(): void
    {
        $this->assertNotSame([], $this->failures('https://internal.example.com/', ['10.20.30.40']));
    }

    #[Test]
    public function hostname_with_one_private_record_among_public_ones_is_rejected(): void
    {
        $this->assertNotSame([], $this->failures('https://mixed.example.com/', ['93.184.216.34', '192.168.0.9']));
    }

    #[Test]
    public function hostname_resolving_to_ipv6_link_local_is_rejected(): void
    {
        $this->assertNotSame([], $this->failures('https://v6.example.com/', ['fe80::abcd']));
    }

    #[Test]
    public function unresolvable_hostname_is_rejected(): void
    {
        $this->assertNotSame([], $this->failures('https://does-not-exist.example.com/', []));
    }

    #[Test]
    public function credentials_in_url_are_rejected(): void
    {
        $this->assertNotSame([], $this->failures('https://user:pw@hooks.example.com/'));
    }

    #[Test]
    public function disallowed_scheme_is_rejected(): void
    {
        $this->assertNotSame([], $this->failures('ftp://hooks.example.com/'));
        $this->assertNotSame([], $this->failures('http://hooks.example.com/', ['93.184.216.34'], ['https']));
        $this->assertSame([], $this->failures('https://hooks.example.com/', ['93.184.216.34'], ['https']));
    }

    #[Test]
    public function garbage_is_rejected(): void
    {
        $this->assertNotSame([], $this->failures('not a url'));
        $this->assertNotSame([], $this->failures(''));
        $this->assertNotSame([], $this->failures('https:///path-only'));
    }

    #[Test]
    public function is_public_ip_helper_matches_expectations(): void
    {
        $this->assertTrue(PublicUrlRule::isPublicIp('8.8.8.8'));
        $this->assertTrue(PublicUrlRule::isPublicIp('2001:4860:4860::8888'));
        $this->assertFalse(PublicUrlRule::isPublicIp('192.0.0.1'));
        $this->assertFalse(PublicUrlRule::isPublicIp('198.18.0.1'));
        $this->assertFalse(PublicUrlRule::isPublicIp('2001:db8::1'));
        $this->assertFalse(PublicUrlRule::isPublicIp('not-an-ip'));
    }
}
