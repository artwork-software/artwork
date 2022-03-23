<?php

use Spatie\LaravelSettings\Exceptions\SettingAlreadyExists;
use Spatie\LaravelSettings\Migrations\SettingsMigration;

class CreateGeneralSettings extends SettingsMigration
{
    /**
     * @throws SettingAlreadyExists
     */
    public function up(): void
    {
        $this->migrator->add('general.company_name', "DTH");
        $this->migrator->add('general.setup_finished', false);
        $this->migrator->add('general.logo_path', "");
        $this->migrator->add('general.banner_path', "");
    }
}
