<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('calendar.project_creation_from_events_enabled', true);
    }

    public function down(): void
    {
        $this->migrator->delete('calendar.project_creation_from_events_enabled');
    }
};
