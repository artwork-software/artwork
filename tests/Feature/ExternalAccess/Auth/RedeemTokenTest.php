<?php

namespace Tests\Feature\ExternalAccess\Auth;

use Artwork\Modules\ExternalAccess\Models\ExternalAccess;
use Artwork\Modules\ExternalAccess\Models\ExternalInvitation;
use Artwork\Modules\ExternalAccess\Models\ExternalLoginToken;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class RedeemTokenTest extends TestCase
{
    private function makeToken(ExternalAccess $external, string $plain): ExternalLoginToken
    {
        return ExternalLoginToken::factory()->create([
            'external_access_id' => $external->id,
            'token_hash' => hash('sha256', $plain),
            'expires_at' => now()->addMinutes(15),
        ]);
    }

    #[Test]
    public function valid_token_authenticates_external_user(): void
    {
        $external = ExternalAccess::factory()->active()->create();
        $plain = Str::random(64);
        $this->makeToken($external, $plain);

        $response = $this->get(route('external.login.redeem', ['token' => $plain]));

        $response->assertRedirect(route('external.dashboard'));
        $this->assertSame($external->id, Auth::guard('external')->id());
    }

    #[Test]
    public function expired_token_returns_generic_error_and_does_not_authenticate(): void
    {
        $external = ExternalAccess::factory()->active()->create();
        $plain = Str::random(64);
        ExternalLoginToken::factory()->expired()->create([
            'external_access_id' => $external->id,
            'token_hash' => hash('sha256', $plain),
        ]);

        $response = $this->get(route('external.login.redeem', ['token' => $plain]));

        $response->assertRedirect(route('external.login.invalid'));
        $this->assertFalse(Auth::guard('external')->check());
    }

    #[Test]
    public function used_token_returns_generic_error_and_does_not_authenticate(): void
    {
        $external = ExternalAccess::factory()->active()->create();
        $plain = Str::random(64);
        ExternalLoginToken::factory()->used()->create([
            'external_access_id' => $external->id,
            'token_hash' => hash('sha256', $plain),
            'expires_at' => now()->addMinutes(15),
        ]);

        $response = $this->get(route('external.login.redeem', ['token' => $plain]));

        $response->assertRedirect(route('external.login.invalid'));
        $this->assertFalse(Auth::guard('external')->check());
    }

    #[Test]
    public function revoked_user_token_returns_generic_error_even_with_valid_token(): void
    {
        $external = ExternalAccess::factory()->active()->revoked()->create();
        $plain = Str::random(64);
        $this->makeToken($external, $plain);

        $response = $this->get(route('external.login.redeem', ['token' => $plain]));

        $response->assertRedirect(route('external.login.invalid'));
        $this->assertFalse(Auth::guard('external')->check());
    }

    #[Test]
    public function redeeming_token_marks_used_at_and_updates_last_login_at(): void
    {
        $external = ExternalAccess::factory()->active()->create();
        $plain = Str::random(64);
        $token = $this->makeToken($external, $plain);

        $this->get(route('external.login.redeem', ['token' => $plain]));

        $this->assertNotNull($token->fresh()->used_at);
        $this->assertNotNull($external->fresh()->last_login_at);
    }

    #[Test]
    public function redeeming_first_token_sets_first_redeemed_at_on_invitation(): void
    {
        $external = ExternalAccess::factory()->active()->create();
        $invitation = ExternalInvitation::factory()->create([
            'external_access_id' => $external->id,
            'first_redeemed_at' => null,
        ]);
        $plain = Str::random(64);
        $this->makeToken($external, $plain);

        $this->get(route('external.login.redeem', ['token' => $plain]));

        $this->assertNotNull($invitation->fresh()->first_redeemed_at);
    }

    #[Test]
    public function redeeming_token_can_only_be_used_once_under_repeated_requests(): void
    {
        $external = ExternalAccess::factory()->active()->create();
        $plain = Str::random(64);
        $this->makeToken($external, $plain);

        $first = $this->get(route('external.login.redeem', ['token' => $plain]));
        $first->assertRedirect(route('external.dashboard'));

        // Force the guard to clear so the next call goes back through redemption.
        Auth::guard('external')->logout();

        $second = $this->get(route('external.login.redeem', ['token' => $plain]));
        $second->assertRedirect(route('external.login.invalid'));
        $this->assertFalse(Auth::guard('external')->check());
    }

    #[Test]
    public function redeem_token_route_validates_token_length(): void
    {
        $response = $this->get('/external/login/short');

        $response->assertNotFound();
    }
}
