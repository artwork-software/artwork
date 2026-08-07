<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('shift-settings.shift_confirmation_enabled', false);
        $this->migrator->add('shift-settings.shift_confirmation_in_history', false);
    }

    public function down(): void
    {
        $this->migrator->delete('shift-settings.shift_confirmation_enabled');
        $this->migrator->delete('shift-settings.shift_confirmation_in_history');
    }
};
