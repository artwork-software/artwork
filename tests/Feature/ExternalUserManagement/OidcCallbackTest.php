<?php

namespace Tests\Feature\ExternalUserManagement;

use Artwork\Modules\ExternalUserManagement\Models\ExternalUserSource;
use Artwork\Modules\ExternalUserManagement\Service\OidcService;
use Artwork\Modules\User\Models\User;
use Illuminate\Support\Facades\Auth;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Deckt die Callback-Hälfte des OIDC-Logins ab: OidcAuthController::callback →
 * IdentityResolutionService → Provisionierung/Login. Der Token-/Userinfo-Abruf
 * (reines Socialite) ist über einen OidcService-Mock ersetzt; verifiziert wird
 * die artwork-eigene Orchestrierung (Domain, Auflösung, email_verified, Login).
 */
final class OidcCallbackTest extends FeatureTestCase
{
    private function source(array $config = []): ExternalUserSource
    {
        return ExternalUserSource::query()->create([
            'name' => 'IdP',
            'active' => true,
            'type' => 'identity_provider',
            'config' => array_merge([
                'discovery_url' => 'https://idp.example.com/.well-known/openid-configuration',
                'client_id' => 'artwork',
                'client_secret' => 'secret',
                'allowed_domains' => [],
            ], $config),
        ]);
    }

    private function fakeClaims(array $overrides = []): array
    {
        return array_merge([
            'identifier' => 'subject-123',
            'email' => 'oidc.user@example.com',
            'first_name' => 'Oidc',
            'last_name' => 'User',
            'groups' => [],
            'email_verified' => true,
            'meta_data' => ['sub' => 'subject-123'],
        ], $overrides);
    }

    #[Test]
    public function a_successful_callback_provisions_and_logs_in_the_user(): void
    {
        $source = $this->source();

        $this->mock(OidcService::class, function ($mock) use ($source): void {
            $mock->shouldReceive('userFromCallback')->andReturn($this->fakeClaims());
        });

        $response = $this->get(route('auth.oidc.callback', ['externalUserSource' => $source->id]));

        $response->assertRedirect();
        $this->assertTrue(Auth::check());

        $user = User::query()->where('email', 'oidc.user@example.com')->first();
        $this->assertNotNull($user);
        $this->assertSame('oidc', $user->auth_provider);
        $this->assertSame('subject-123', $user->auth_provider_id);
        $this->assertNotNull($user->email_verified_at);
        $this->assertTrue(Auth::id() === $user->id);
    }

    #[Test]
    public function a_disallowed_email_domain_is_rejected(): void
    {
        $source = $this->source(['allowed_domains' => ['trusted.com']]);

        $this->mock(OidcService::class, function ($mock): void {
            $mock->shouldReceive('userFromCallback')->andReturn($this->fakeClaims([
                'email' => 'intruder@evil.com',
            ]));
        });

        $response = $this->get(route('auth.oidc.callback', ['externalUserSource' => $source->id]));

        $response->assertRedirect(route('login'));
        $this->assertFalse(Auth::check());
        $this->assertNull(User::query()->where('email', 'intruder@evil.com')->first());
    }

    #[Test]
    public function a_second_login_resolves_the_same_user_by_subject(): void
    {
        $source = $this->source();

        $this->mock(OidcService::class, function ($mock): void {
            $mock->shouldReceive('userFromCallback')->andReturn($this->fakeClaims());
        });

        $this->get(route('auth.oidc.callback', ['externalUserSource' => $source->id]));
        $firstId = User::query()->where('email', 'oidc.user@example.com')->value('id');

        // frischer Request: erneut einloggen
        $this->app['auth']->guard(config('fortify.guard'))->logout();
        $this->get(route('auth.oidc.callback', ['externalUserSource' => $source->id]));

        $this->assertSame(1, User::query()->where('email', 'oidc.user@example.com')->count());
        $this->assertSame($firstId, User::query()->where('email', 'oidc.user@example.com')->value('id'));
    }
}
