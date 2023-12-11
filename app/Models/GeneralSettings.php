<?php

namespace App\Models;

use Spatie\LaravelSettings\Settings;

class GeneralSettings extends Settings
{
    public string $company_name;

    public bool $setup_finished;

    public string $banner_path;

    public string $big_logo_path;

    public string $small_logo_path;

    public string $impressum_link;

    public string $business_name;

    public string $privacy_link;

    public string $email_footer;

    public static function group(): string
    {
        return 'general';
    }
}
