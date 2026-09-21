<?php

namespace Tests\Feature;

use Artwork\Core\Http\Middleware\TrustProxies;
use Artwork\Core\Http\Middleware\VerifyCsrfToken;
use Artwork\Modules\GeneralSettings\Models\GeneralSettings;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;

/**
 * Regressionstests zu den Grundschutz-Befunden des Sicherheits-Audits vom 21.09.2026
 * (Sofortmassnahmen 3 und 10): Security-Header, CSP-Nonce, HSTS, Trusted Proxies, CSRF, CORS.
 */
final class SecurityAuditHeadersRegressionTest extends FeatureTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $settings = app(GeneralSettings::class);
        $settings->setup_finished = true;
        $settings->save();
    }

    #[Test]
    public function login_page_carries_the_baseline_security_headers(): void
    {
        $response = $this->get('http://localhost/login');

        $response->assertOk();
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
    }

    #[Test]
    public function csp_is_report_only_by_default_and_nonce_matches_the_inline_scripts(): void
    {
        config(['security.csp_enforce' => false]);

        $response = $this->get('http://localhost/login');

        $response->assertOk();
        $response->assertHeaderMissing('Content-Security-Policy');

        $csp = $response->headers->get('Content-Security-Policy-Report-Only');
        $this->assertNotNull($csp);
        $this->assertMatchesRegularExpression("/script-src 'self' 'nonce-([A-Za-z0-9]+)'/", $csp);
        $this->assertStringContainsString("frame-ancestors 'self'", $csp);
        $this->assertStringContainsString("object-src 'none'", $csp);

        preg_match("/'nonce-([A-Za-z0-9]+)'/", $csp, $matches);
        $this->assertStringContainsString('nonce="' . $matches[1] . '"', $response->getContent());
    }

    #[Test]
    public function csp_is_enforced_when_the_switch_is_on(): void
    {
        config(['security.csp_enforce' => true]);

        $response = $this->get('http://localhost/login');

        $response->assertOk();
        $response->assertHeaderMissing('Content-Security-Policy-Report-Only');
        $this->assertStringContainsString("'nonce-", (string) $response->headers->get('Content-Security-Policy'));
    }

    #[Test]
    public function hsts_is_only_sent_on_https_requests(): void
    {
        $this->get('https://localhost/login')
            ->assertOk()
            ->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');

        $this->get('http://localhost/login')
            ->assertOk()
            ->assertHeaderMissing('Strict-Transport-Security');
    }

    #[Test]
    public function trusted_proxies_default_is_not_a_wildcard(): void
    {
        $this->assertNotSame('*', config('app.trusted_proxies'));
        $this->assertStringContainsString('172.16.0.0/12', (string) config('app.trusted_proxies'));
    }

    #[Test]
    public function forwarded_for_from_a_public_client_is_ignored(): void
    {
        $request = Request::create('http://localhost/', 'GET', server: [
            'REMOTE_ADDR' => '203.0.113.10',
            'HTTP_X_FORWARDED_FOR' => '198.51.100.7',
        ]);

        $ip = (new TrustProxies())->handle($request, fn (Request $request) => $request->ip());

        $this->assertSame('203.0.113.10', $ip);
    }

    #[Test]
    public function forwarded_for_from_a_docker_proxy_is_honoured(): void
    {
        $request = Request::create('http://localhost/', 'GET', server: [
            'REMOTE_ADDR' => '172.18.0.1',
            'HTTP_X_FORWARDED_FOR' => '198.51.100.7',
        ]);

        $ip = (new TrustProxies())->handle($request, fn (Request $request) => $request->ip());

        $this->assertSame('198.51.100.7', $ip);
    }

    #[Test]
    public function csrf_middleware_has_no_wildcard_exception(): void
    {
        $except = (new \ReflectionClass(VerifyCsrfToken::class))->getDefaultProperties()['except'];

        $this->assertSame([], $except);
    }

    #[Test]
    public function cors_does_not_reflect_arbitrary_origins(): void
    {
        $this->assertNotContains('*', config('cors.allowed_origins'));
        $this->assertContains(config('app.url'), config('cors.allowed_origins'));
    }
}
