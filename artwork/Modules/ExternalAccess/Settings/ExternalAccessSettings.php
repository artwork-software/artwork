<?php

namespace Artwork\Modules\ExternalAccess\Settings;

use Spatie\LaravelSettings\Settings;

class ExternalAccessSettings extends Settings
{
    public string $company_name_override;
    public int $default_crm_access_months;
    public int $default_tab_access_days;
    public int $login_token_lifetime_minutes;
    public int $session_idle_timeout_minutes;
    public int $session_absolute_lifetime_minutes;
    public int $rate_limit_request_link_per_email_per_hour;
    public int $rate_limit_request_link_per_ip_per_hour;

    public static function group(): string
    {
        return 'external_access';
    }
}
