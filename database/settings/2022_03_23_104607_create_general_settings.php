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
        $this->migrator->add('general.big_logo_path', "/logo/artwork_logo_big.svg");
        $this->migrator->add('general.small_logo_path', "/logo/artwork_logo_small.svg");
        $this->migrator->add('general.banner_path', "/banner/default_banner.svg");
        $this->migrator->add('general.impressum_link', "");
        $this->migrator->add('general.business_name', "Unsere Organisation");
        $this->migrator->add('general.privacy_link', "");
        $this->migrator->add('general.email_footer', "");
    }
}
