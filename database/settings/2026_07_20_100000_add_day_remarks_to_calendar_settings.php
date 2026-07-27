<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('calendar.day_remarks_enabled', false);
        $this->migrator->add('calendar.day_remarks_mandatory', false);
    }

    public function down(): void
    {
        $this->migrator->delete('calendar.day_remarks_enabled');
        $this->migrator->delete('calendar.day_remarks_mandatory');
    }
};
