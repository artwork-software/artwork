<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration {
    public function up(): void
    {
        $this->migrator->add('shift-settings.project_assignments_enabled', true);
    }

    public function down(): void
    {
        $this->migrator->delete('shift-settings.project_assignments_enabled');
    }
};
