<?php

namespace App\Models;

use Spatie\LaravelSettings\Settings;

class GeneralSettings extends Settings
{
    public string $company_name;

    public bool $setup_finished;

    public static function group(): string
    {
        return 'general';
    }

}
