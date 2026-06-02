<?php

namespace Tests\Feature\ExternalAccess\Auth;

use Artwork\Modules\ExternalAccess\Models\ExternalAccess;
use Artwork\Modules\ExternalAccess\Models\ExternalLoginToken;
use Artwork\Modules\ExternalAccess\Notifications\ExternalLoginLinkNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class RequestLoginLinkTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
    }

    #[Test]
    public function unknown_email_returns_generic_response_without_sending_mail(): void
    {
        $response = $this->post(route('external.login.request'), [
            'email' => 'nobody@example.com',
        ]);

        $response->assertRedirect(route('external.login.link-sent'));
        Notification::assertNothingSent();
        $this->assertSame(0, ExternalLoginToken::query()->count());
    }

    #[Test]
    public function revoked_external_returns_generic_response_without_sending_mail(): void
    {
        $external = ExternalAccess::factory()->active()->revoked()->create();

        $response = $this->post(route('external.login.request'), [
            'email' => $external->email,
        ]);

        $response->assertRedirect(route('external.login.link-sent'));
        Notification::assertNothingSent();
        $this->assertSame(0, ExternalLoginToken::query()->count());
    }

    #[Test]
    public function expired_external_returns_generic_response_without_sending_mail(): void
    {
        $external = ExternalAccess::factory()->crmAccessExpired()->create();

        $response = $this->post(route('external.login.request'), [
            'email' => $external->email,
        ]);

        $response->assertRedirect(route('external.login.link-sent'));
        Notification::assertNothingSent();
        $this->assertSame(0, ExternalLoginToken::query()->count());
    }

    #[Test]
    public function valid_external_receives_mail_with_token(): void
    {
        $external = ExternalAccess::factory()->active()->create();

        $response = $this->post(route('external.login.request'), [
            'email' => $external->email,
        ]);

        $response->assertRedirect(route('external.login.link-sent'));
        Notification::assertSentOnDemand(ExternalLoginLinkNotification::class);
        $this->assertSame(1, ExternalLoginToken::query()->where('external_access_id', $external->id)->count());
    }

    #[Test]
    public function request_link_throttled_after_3_attempts_per_hour_per_email(): void
    {
        $external = ExternalAccess::factory()->active()->create();

        for ($i = 0; $i < 3; $i++) {
            $this->post(route('external.login.request'), ['email' => $external->email])
                ->assertRedirect(route('external.login.link-sent'));
        }

        $response = $this->post(route('external.login.request'), ['email' => $external->email]);
        $response->assertStatus(429);
    }

    #[Test]
    public function plain_token_never_appears_in_database(): void
    {
        $external = ExternalAccess::factory()->active()->create();

        $capturedToken = null;
        Notification::fake();

        $this->post(route('external.login.request'), ['email' => $external->email]);

        Notification::assertSentOnDemand(
            ExternalLoginLinkNotification::class,
            function (ExternalLoginLinkNotification $notification) use (&$capturedToken) {
                $capturedToken = $notification->plainToken;
                return true;
            }
        );

        $this->assertNotNull($capturedToken);
        $this->assertSame(0, ExternalLoginToken::query()->where('token_hash', $capturedToken)->count());

        $tokenRow = ExternalLoginToken::query()->where('external_access_id', $external->id)->first();
        $this->assertNotNull($tokenRow);
        $this->assertSame(hash('sha256', $capturedToken), $tokenRow->token_hash);
    }

    #[Test]
    public function plain_token_never_appears_in_logs(): void
    {
        $external = ExternalAccess::factory()->active()->create();
        $logBuffer = [];

        Log::listen(function ($message) use (&$logBuffer): void {
            $logBuffer[] = $message;
        });

        $capturedToken = null;
        $this->post(route('external.login.request'), ['email' => $external->email]);

        Notification::assertSentOnDemand(
            ExternalLoginLinkNotification::class,
            function (ExternalLoginLinkNotification $notification) use (&$capturedToken) {
                $capturedToken = $notification->plainToken;
                return true;
            }
        );

        foreach ($logBuffer as $entry) {
            $context = method_exists($entry, '__get') ? (string) ($entry->message ?? '') : '';
            $this->assertStringNotContainsString((string) $capturedToken, $context);
        }
    }

    #[Test]
    public function email_validation_rejects_malformed_address(): void
    {
        $response = $this->post(route('external.login.request'), [
            'email' => 'not-an-email',
        ]);

        $response->assertSessionHasErrors('email');
        Notification::assertNothingSent();
    }
}
