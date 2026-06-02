<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration {
    public function up(): void
    {
        $this->migrator->add('external_access.company_name_override', '');
        $this->migrator->add('external_access.default_crm_access_months', 12);
        $this->migrator->add('external_access.default_tab_access_days', 90);
        $this->migrator->add('external_access.login_token_lifetime_minutes', 15);
        $this->migrator->add('external_access.session_idle_timeout_minutes', 120);
        $this->migrator->add('external_access.session_absolute_lifetime_minutes', 480);
        $this->migrator->add('external_access.rate_limit_request_link_per_email_per_hour', 3);
        $this->migrator->add('external_access.rate_limit_request_link_per_ip_per_hour', 10);
    }

    public function down(): void
    {
        foreach ([
            'company_name_override',
            'default_crm_access_months',
            'default_tab_access_days',
            'login_token_lifetime_minutes',
            'session_idle_timeout_minutes',
            'session_absolute_lifetime_minutes',
            'rate_limit_request_link_per_email_per_hour',
            'rate_limit_request_link_per_ip_per_hour',
        ] as $key) {
            $this->migrator->delete("external_access.{$key}");
        }
    }
};
