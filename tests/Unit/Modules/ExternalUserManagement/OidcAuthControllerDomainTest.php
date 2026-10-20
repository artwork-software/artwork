<?php

namespace Tests\Unit\Modules\ExternalUserManagement;

use Artwork\Modules\ExternalUserManagement\Models\ExternalUserSource;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class OidcAuthControllerDomainTest extends TestCase
{
    /**
     * Der OIDC-Callback ruft die Allowlist mit $allowWhenListEmpty = false auf.
     */
    private function isAllowed(array $allowedDomains, string $email): bool
    {
        $source = new ExternalUserSource();
        $source->config = ['allowed_domains' => $allowedDomains];

        return $source->allowsEmailDomain($email, false);
    }

    #[Test]
    public function it_allows_a_matching_domain(): void
    {
        $this->assertTrue($this->isAllowed(['example.com'], 'user@example.com'));
    }

    #[Test]
    public function it_matches_case_insensitively_and_ignores_at_prefixes(): void
    {
        $this->assertTrue($this->isAllowed(['@Example.COM'], 'User@example.com'));
    }

    #[Test]
    public function it_denies_a_non_matching_domain(): void
    {
        $this->assertFalse($this->isAllowed(['example.com'], 'user@evil.com'));
    }

    #[Test]
    public function it_denies_every_domain_when_the_allowlist_is_empty(): void
    {
        $this->assertFalse($this->isAllowed([], 'user@example.com'));
    }

    #[Test]
    public function oidc_authentication_routes_are_throttled_for_guests(): void
    {
        foreach (['auth.oidc.redirect', 'auth.oidc.callback'] as $routeName) {
            $middleware = Route::getRoutes()->getByName($routeName)->gatherMiddleware();

            $this->assertContains('guest', $middleware);
            $this->assertContains('throttle:20,1', $middleware);
        }
    }
}
