<?php

namespace Tests\Feature\ExternalAccess\AdminSettings;

use Artwork\Modules\ExternalAccess\Settings\ExternalAccessSettings;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ExternalAccessSettingsTest extends TestCase
{
    #[Test]
    public function defaults_are_set_after_migration(): void
    {
        $settings = app(ExternalAccessSettings::class);

        $this->assertSame(12, $settings->default_crm_access_months);
        $this->assertSame(90, $settings->default_tab_access_days);
        $this->assertSame(15, $settings->login_token_lifetime_minutes);
        $this->assertSame(120, $settings->session_idle_timeout_minutes);
        $this->assertSame(480, $settings->session_absolute_lifetime_minutes);
        $this->assertSame(3, $settings->rate_limit_request_link_per_email_per_hour);
        $this->assertSame(10, $settings->rate_limit_request_link_per_ip_per_hour);
        $this->assertSame('', $settings->company_name_override);
    }

    #[Test]
    public function values_can_be_updated_and_persist(): void
    {
        $settings = app(ExternalAccessSettings::class);
        $settings->default_crm_access_months = 24;
        $settings->company_name_override = 'Acme GmbH';
        $settings->save();

        $reloaded = app(ExternalAccessSettings::class);
        $this->assertSame(24, $reloaded->default_crm_access_months);
        $this->assertSame('Acme GmbH', $reloaded->company_name_override);
    }
}
