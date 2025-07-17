<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('general.start_night_time', '22:00');
        $this->migrator->add('general.end_night_time', '06:00');
    }
};
