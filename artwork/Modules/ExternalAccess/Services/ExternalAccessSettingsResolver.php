<?php

namespace Artwork\Modules\ExternalAccess\Services;

use Artwork\Modules\ExternalAccess\Settings\ExternalAccessSettings;
use Artwork\Modules\GeneralSettings\Models\GeneralSettings;
use Carbon\Carbon;
use Carbon\CarbonInterface;

/**
 * Single source of truth for "effective" external-access configuration values.
 * Reads from ExternalAccessSettings (admin-configurable), with sensible fallbacks.
 */
class ExternalAccessSettingsResolver
{
    public function __construct(
        private readonly ExternalAccessSettings $settings,
        private readonly GeneralSettings $generalSettings,
    ) {
    }

    /**
     * Company name for invitation wording: override -> GeneralSettings.business_name -> app.name.
     */
    public function companyName(): string
    {
        if ($this->settings->company_name_override !== '') {
            return $this->settings->company_name_override;
        }

        return $this->companyNameFallback();
    }

    /**
     * The value used when no override is set (for UI placeholders).
     */
    public function companyNameFallback(): string
    {
        if (($this->generalSettings->business_name ?? '') !== '') {
            return $this->generalSettings->business_name;
        }

        return (string) config('app.name', 'Artwork');
    }

    public function defaultCrmAccessExpiry(?CarbonInterface $from = null): Carbon
    {
        return Carbon::instance($from ? $from->toDateTime() : Carbon::now()->toDateTime())
            ->addMonths($this->settings->default_crm_access_months);
    }

    public function defaultTabAccessExpiry(?CarbonInterface $from = null): Carbon
    {
        return Carbon::instance($from ? $from->toDateTime() : Carbon::now()->toDateTime())
            ->addDays($this->settings->default_tab_access_days);
    }

    public function loginTokenLifetimeMinutes(): int
    {
        return $this->settings->login_token_lifetime_minutes;
    }

    public function sessionIdleTimeoutMinutes(): int
    {
        return $this->settings->session_idle_timeout_minutes;
    }

    public function sessionAbsoluteLifetimeMinutes(): int
    {
        return $this->settings->session_absolute_lifetime_minutes;
    }

    /**
     * @return array{request_link_per_email_per_hour: int, request_link_per_ip_per_hour: int}
     */
    public function rateLimits(): array
    {
        return [
            'request_link_per_email_per_hour' => $this->settings->rate_limit_request_link_per_email_per_hour,
            'request_link_per_ip_per_hour' => $this->settings->rate_limit_request_link_per_ip_per_hour,
        ];
    }
}
