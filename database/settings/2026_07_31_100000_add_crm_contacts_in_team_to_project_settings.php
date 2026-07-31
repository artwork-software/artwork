<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration {
    public function up(): void
    {
        $this->migrator->add('project.crm_contacts_in_team', false);
    }

    public function down(): void
    {
        $this->migrator->delete('project.crm_contacts_in_team');
    }
};
