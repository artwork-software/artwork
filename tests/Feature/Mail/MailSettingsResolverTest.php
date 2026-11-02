<?php

namespace Tests\Feature\Mail;

use Artwork\Modules\GeneralSettings\Models\GeneralSettings;
use Artwork\Modules\Mail\Services\MailSettingsResolver;
use Artwork\Modules\Mail\Settings\MailSettings;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class MailSettingsResolverTest extends TestCase
{
    private function resolver(): MailSettingsResolver
    {
        return app(MailSettingsResolver::class);
    }

    private function settings(): MailSettings
    {
        return app(MailSettings::class);
    }

    #[Test]
    public function host_uses_db_override_when_set(): void
    {
        config(['mail.fallback.host' => 'env-host.example.com']);

        $settings = $this->settings();
        $settings->host = 'db-host.example.com';
        $settings->save();

        $this->assertSame('db-host.example.com', $this->resolver()->host());
    }

    #[Test]
    public function host_falls_back_to_env_when_db_empty(): void
    {
        config(['mail.fallback.host' => 'env-host.example.com']);

        $settings = $this->settings();
        $settings->host = null;
        $settings->save();

        $this->assertSame('env-host.example.com', $this->resolver()->host());
    }

    #[Test]
    public function empty_string_is_treated_as_not_set_and_falls_back(): void
    {
        config(['mail.fallback.host' => 'env-host.example.com']);

        $settings = $this->settings();
        $settings->host = '';
        $settings->save();

        $this->assertSame('env-host.example.com', $this->resolver()->host());
    }

    #[Test]
    public function password_is_stored_and_returned_verbatim(): void
    {
        config(['mail.fallback.password' => 'env-fallback-pw']);

        $settings = $this->settings();
        $settings->password = '  s3cret with spaces  ';
        $settings->save();

        // Not trimmed — the stored password is returned exactly.
        $this->assertSame('  s3cret with spaces  ', $this->resolver()->password());
    }

    #[Test]
    public function password_falls_back_to_env_when_db_empty(): void
    {
        config(['mail.fallback.password' => 'env-fallback-pw']);

        $settings = $this->settings();
        $settings->password = null;
        $settings->save();

        $this->assertSame('env-fallback-pw', $this->resolver()->password());
    }

    #[Test]
    public function from_address_and_name_override_env(): void
    {
        config([
            'mail.fallback.from_address' => 'env@example.com',
            'mail.fallback.from_name' => 'Env Name',
        ]);

        $settings = $this->settings();
        $settings->from_address = 'db@example.com';
        $settings->from_name = 'DB Name';
        $settings->save();

        $this->assertSame('db@example.com', $this->resolver()->fromAddress());
        $this->assertSame('DB Name', $this->resolver()->fromName());
    }

    #[Test]
    public function a_configured_from_address_overrides_the_explicit_sender_of_outgoing_mails(): void
    {
        $settings = $this->settings();
        $settings->from_address = 'buehne@example.com';
        $settings->from_name = null;
        $settings->save();

        config(['mail.default' => 'array']);

        // Notifications setzen ihren Absender explizit (business_email) — der
        // MessageSending-Listener muss den gepflegten Override trotzdem durchsetzen.
        \Illuminate\Support\Facades\Mail::raw('Test', function ($message): void {
            $message->to('user@example.com')->from('business@example.com', 'Theaterhaus');
        });

        $sent = app('mail.manager')->mailer('array')->getSymfonyTransport()->messages();
        $from = $sent->first()->getOriginalMessage()->getFrom()[0];

        $this->assertSame('buehne@example.com', $from->getAddress());
        // Ohne from_name-Override bleibt der Anzeigename der Mail erhalten.
        $this->assertSame('Theaterhaus', $from->getName());
    }

    #[Test]
    public function without_a_from_override_the_explicit_sender_stays_untouched(): void
    {
        $settings = $this->settings();
        $settings->from_address = null;
        $settings->from_name = null;
        $settings->save();

        config(['mail.default' => 'array']);

        \Illuminate\Support\Facades\Mail::raw('Test', function ($message): void {
            $message->to('user@example.com')->from('business@example.com', 'Theaterhaus');
        });

        $sent = app('mail.manager')->mailer('array')->getSymfonyTransport()->messages();
        $from = $sent->first()->getOriginalMessage()->getFrom()[0];

        $this->assertSame('business@example.com', $from->getAddress());
    }

    #[Test]
    public function effective_config_array_merges_db_and_env(): void
    {
        config([
            'mail.fallback.host' => 'env-host.example.com',
            'mail.fallback.port' => 587,
            'mail.fallback.from_name' => 'Env Name',
        ]);

        $settings = $this->settings();
        $settings->host = 'db-host.example.com';
        $settings->port = null;
        $settings->from_name = 'DB Name';
        $settings->save();

        $config = $this->resolver()->effectiveConfigArray();

        $this->assertSame('db-host.example.com', $config['host']);
        $this->assertSame(587, $config['port']);
        $this->assertSame('DB Name', $config['from_name']);
    }

    #[Test]
    public function env_credentials_are_never_combined_with_an_overridden_host(): void
    {
        config([
            'mail.fallback.host' => 'env-host.example.com',
            'mail.fallback.username' => 'env-user',
            'mail.fallback.password' => 'env-secret',
        ]);

        $settings = $this->settings();
        $settings->host = 'attacker-controlled.example.com';
        $settings->username = null;
        $settings->password = null;
        $settings->save();

        // Kein Credential-Mixing: Die .env-Zugangsdaten dürfen nie an einen
        // admin-konfigurierten Fremd-Host gesendet werden.
        $this->assertNull($this->resolver()->username());
        $this->assertNull($this->resolver()->password());

        // Ohne Host-Override greift der .env-Fallback weiterhin.
        $settings->host = null;
        $settings->save();

        $this->assertSame('env-user', $this->resolver()->username());
        $this->assertSame('env-secret', $this->resolver()->password());
    }

    #[Test]
    public function a_custom_user_name_never_gets_the_env_password(): void
    {
        config([
            'mail.fallback.host' => 'env-host.example.com',
            'mail.fallback.username' => 'env-user',
            'mail.fallback.password' => 'env-secret',
        ]);

        $settings = $this->settings();
        $settings->host = null;
        $settings->username = 'loth@theater.example';
        $settings->password = null;
        $settings->save();

        // Sentry (Theaterhaus Jena): Benutzername aus den Einstellungen + .env-Passwort → 535.
        $this->assertSame('loth@theater.example', $this->resolver()->username());
        $this->assertNull($this->resolver()->password());
        $this->assertSame('settings', $this->resolver()->authenticationSource());
    }

    #[Test]
    public function the_env_host_entered_again_keeps_the_env_credentials(): void
    {
        config([
            'mail.fallback.host' => 'env-host.example.com',
            'mail.fallback.username' => 'env-user',
            'mail.fallback.password' => 'env-secret',
        ]);

        $settings = $this->settings();
        $settings->host = 'ENV-HOST.example.com';
        $settings->username = null;
        $settings->password = null;
        $settings->save();

        // Vorher fiel die Anmeldung bei jedem Host-Override weg → „Client host rejected“.
        $this->assertSame('env-user', $this->resolver()->username());
        $this->assertSame('env-secret', $this->resolver()->password());
        $this->assertSame('env', $this->resolver()->authenticationSource());

        $settings->host = 'relay.example.com';
        $settings->save();

        $this->assertSame('none', $this->resolver()->authenticationSource());
    }

    #[Test]
    public function the_default_sender_follows_the_notification_chain_and_skips_empty_values(): void
    {
        config([
            'mail.system_mail' => 'system@artwork.example',
            'mail.fallback.from_address' => 'hello@example.com',
        ]);
        $generalSettings = app(GeneralSettings::class);
        $generalSettings->business_email = 'support@theater.example';
        $generalSettings->save();
        $settings = $this->settings();
        $settings->from_address = null;
        $settings->save();

        $this->assertSame('support@theater.example', $this->resolver()->fromAddress());

        $generalSettings->business_email = '';
        $generalSettings->save();
        $this->assertSame('system@artwork.example', $this->resolver()->fromAddress());

        config(['mail.system_mail' => '']);
        $this->assertSame('hello@example.com', $this->resolver()->fromAddress());
    }

    #[Test]
    public function a_mail_without_sender_gets_the_default_sender(): void
    {
        config([
            'mail.default' => 'array',
            'mail.system_mail' => '',
            'mail.from.address' => null,
        ]);
        $generalSettings = app(GeneralSettings::class);
        $generalSettings->business_email = 'support@theater.example';
        $generalSettings->save();
        $settings = $this->settings();
        $settings->from_address = null;
        $settings->save();
        \Illuminate\Support\Facades\Mail::purge('array');

        // Sentry HARZTHEATER-12: leere Einladungs-Adresse + leeres SYSTEM_MAIL → kein From-Header.
        \Illuminate\Support\Facades\Mail::raw('Test', function ($message): void {
            $message->to('user@example.com');
        });

        $sent = app('mail.manager')->mailer('array')->getSymfonyTransport()->messages();
        $this->assertSame(
            'support@theater.example',
            $sent->last()->getOriginalMessage()->getFrom()[0]->getAddress()
        );
    }
}
