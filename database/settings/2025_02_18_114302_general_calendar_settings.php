<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('calendar.start', '00:00');
        $this->migrator->add('calendar.end', '08:00');
    }
};
