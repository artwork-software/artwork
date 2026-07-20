<?php

namespace Tests\Feature\ExternalUserManagement;

use Artwork\Modules\User\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

// Erbt bewusst von der Basis-TestCase (nicht FeatureTestCase): letztere faked den
// Mail-Manager, was die Console-Kernel-Command-Discovery bei $this->artisan() bricht.
final class BreakGlassCommandTest extends TestCase
{
    #[Test]
    public function it_decouples_an_idp_bound_account_and_sends_a_reset_link(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'sso@example.com',
            'auth_provider' => 'oidc',
            'auth_provider_id' => 'subject-1',
            'auth_provider_issuer' => 'https://idp.example.com',
            'ad_managed' => true,
            'ad_identifier' => 'subject-1',
        ]);

        $this->artisan('auth:break-glass', ['user' => 'sso@example.com'])
            ->assertSuccessful();

        $fresh = $user->fresh();
        $this->assertSame('local', $fresh->auth_provider);
        $this->assertNull($fresh->auth_provider_id);
        $this->assertNull($fresh->auth_provider_issuer);
        $this->assertFalse($fresh->ad_managed);

        Notification::assertSentTo($fresh, ResetPassword::class);
    }

    #[Test]
    public function it_is_a_noop_for_already_local_accounts(): void
    {
        $user = User::factory()->create([
            'email' => 'local@example.com',
            'auth_provider' => 'local',
        ]);

        $this->artisan('auth:break-glass', ['user' => 'local@example.com', '--no-reset' => true])
            ->assertSuccessful();

        $this->assertSame('local', $user->fresh()->auth_provider);
    }
}
