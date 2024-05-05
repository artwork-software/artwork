<?php

namespace Artwork\Modules\GeneralSettings\Models;

use Spatie\LaravelSettings\Settings;

class GeneralSettings extends Settings
{
    public string $page_title;
    public string $company_name;

    public bool $setup_finished;

    public string $banner_path;

    public string $big_logo_path;

    public string $small_logo_path;

    public string $impressum_link;

    public string $business_name;

    public string $privacy_link;

    public string $email_footer;

    public string $business_email;

    public bool $budget_account_management_global;

    public static function group(): string
    {
        return 'general';
    }
}
