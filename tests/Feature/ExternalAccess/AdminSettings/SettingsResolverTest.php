<?php

namespace Tests\Feature\ExternalAccess\AdminSettings;

use Artwork\Modules\ExternalAccess\Services\ExternalAccessSettingsResolver;
use Artwork\Modules\ExternalAccess\Settings\ExternalAccessSettings;
use Artwork\Modules\GeneralSettings\Models\GeneralSettings;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class SettingsResolverTest extends TestCase
{
    private function resolver(): ExternalAccessSettingsResolver
    {
        return app(ExternalAccessSettingsResolver::class);
    }

    #[Test]
    public function company_name_returns_override_when_set(): void
    {
        $settings = app(ExternalAccessSettings::class);
        $settings->company_name_override = 'Override Co';
        $settings->save();

        $this->assertSame('Override Co', $this->resolver()->companyName());
    }

    #[Test]
    public function company_name_falls_back_to_business_name(): void
    {
        $settings = app(ExternalAccessSettings::class);
        $settings->company_name_override = '';
        $settings->save();

        $general = app(GeneralSettings::class);
        $general->business_name = 'Business Name Co';
        $general->save();

        $this->assertSame('Business Name Co', $this->resolver()->companyName());
    }

    #[Test]
    public function default_crm_access_expiry_uses_configured_months(): void
    {
        $settings = app(ExternalAccessSettings::class);
        $settings->default_crm_access_months = 6;
        $settings->save();

        $from = Carbon::parse('2026-01-01 00:00:00');
        $expiry = $this->resolver()->defaultCrmAccessExpiry($from);

        $this->assertSame('2026-07-01', $expiry->format('Y-m-d'));
    }

    #[Test]
    public function default_tab_access_expiry_uses_configured_days(): void
    {
        $settings = app(ExternalAccessSettings::class);
        $settings->default_tab_access_days = 30;
        $settings->save();

        $from = Carbon::parse('2026-01-01 00:00:00');
        $expiry = $this->resolver()->defaultTabAccessExpiry($from);

        $this->assertSame('2026-01-31', $expiry->format('Y-m-d'));
    }
}
