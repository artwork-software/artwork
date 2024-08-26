<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('module_settings.projects', true);
        $this->migrator->add('module_settings.room_assignment', true);
        $this->migrator->add('module_settings.shift_plan', true);
        $this->migrator->add('module_settings.inventory', true);
        $this->migrator->add('module_settings.tasks', true);
        $this->migrator->add('module_settings.sources_of_funding', true);
        $this->migrator->add('module_settings.users', true);
        $this->migrator->add('module_settings.contracts', true);
    }
};
