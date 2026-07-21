<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('shift-settings.granular_permissions_enabled', false);
        $this->migrator->add('shift-settings.hide_uncommitted_shifts_from_own_roster', false);
    }
};
