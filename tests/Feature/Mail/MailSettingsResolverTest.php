<?php

namespace Tests\Feature\Mail;

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
}
