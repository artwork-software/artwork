<?php

namespace Tests\Feature\ExternalAccess\Auth;

use Artwork\Modules\ExternalAccess\Models\ExternalAccess;
use Artwork\Modules\ExternalAccess\Settings\ExternalAccessSettings;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\ExternalAccess\ExternalAccessTestCase as TestCase;

/**
 * Die Limits für Link-Anfragen kommen aus den Einstellungen (pro E-Mail und pro IP je Stunde)
 * und müssen im Rate-Limiter tatsächlich ankommen — nicht nur die Config-Standardwerte.
 */
final class RequestLinkRateLimitSettingsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
    }

    private function limits(int $perEmail, int $perIp): void
    {
        $settings = app(ExternalAccessSettings::class);
        $settings->rate_limit_request_link_per_email_per_hour = $perEmail;
        $settings->rate_limit_request_link_per_ip_per_hour = $perIp;
        $settings->save();
    }

    #[Test]
    public function per_email_limit_from_settings_is_enforced(): void
    {
        $this->limits(perEmail: 1, perIp: 100);
        $external = ExternalAccess::factory()->active()->create();

        $this->post(route('external.login.request'), ['email' => $external->email])
            ->assertRedirect(route('external.login.link-sent'));

        $this->post(route('external.login.request'), ['email' => $external->email])
            ->assertStatus(429);
    }

    #[Test]
    public function per_email_limit_is_case_insensitive(): void
    {
        $this->limits(perEmail: 1, perIp: 100);
        $external = ExternalAccess::factory()->active()->create(['email' => 'gast@example.test']);

        $this->post(route('external.login.request'), ['email' => 'gast@example.test'])
            ->assertRedirect(route('external.login.link-sent'));

        $this->post(route('external.login.request'), ['email' => 'GAST@EXAMPLE.TEST'])
            ->assertStatus(429);
    }

    #[Test]
    public function per_ip_limit_from_settings_is_enforced_across_different_emails(): void
    {
        $this->limits(perEmail: 100, perIp: 2);

        $this->post(route('external.login.request'), ['email' => 'a@example.test'])
            ->assertRedirect(route('external.login.link-sent'));
        $this->post(route('external.login.request'), ['email' => 'b@example.test'])
            ->assertRedirect(route('external.login.link-sent'));

        $this->post(route('external.login.request'), ['email' => 'c@example.test'])
            ->assertStatus(429);
    }

    #[Test]
    public function unknown_emails_count_against_the_limit_too(): void
    {
        // Sonst könnte man über unbekannte Adressen die Existenz bekannter Adressen ausprobieren.
        $this->limits(perEmail: 1, perIp: 100);

        $this->post(route('external.login.request'), ['email' => 'niemand@example.test'])
            ->assertRedirect(route('external.login.link-sent'));

        $this->post(route('external.login.request'), ['email' => 'niemand@example.test'])
            ->assertStatus(429);
    }
}
