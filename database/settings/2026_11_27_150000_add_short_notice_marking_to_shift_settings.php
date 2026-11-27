<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration {
    public function up(): void
    {
        $this->migrator->add('shift-settings.short_notice_marking_enabled', false);
        $this->migrator->add('shift-settings.short_notice_cutoff_time', '14:00');
    }

    public function down(): void
    {
        $this->migrator->delete('shift-settings.short_notice_marking_enabled');
        $this->migrator->delete('shift-settings.short_notice_cutoff_time');
    }
};
