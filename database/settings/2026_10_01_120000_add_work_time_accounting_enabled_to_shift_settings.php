<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration {
    public function up(): void
    {
        $this->migrator->add('shift-settings.work_time_accounting_enabled', true);
    }

    public function down(): void
    {
        $this->migrator->delete('shift-settings.work_time_accounting_enabled');
    }
};
