<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

class AddLetterheadFieldsToGeneralSettings extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('general.letterhead_name', '');
        $this->migrator->add('general.letterhead_street', '');
        $this->migrator->add('general.letterhead_zip_code', '');
        $this->migrator->add('general.letterhead_city', '');
        $this->migrator->add('general.letterhead_email', '');
    }
}
