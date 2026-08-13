<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration {
    public function up(): void
    {
        $this->migrator->add('project.state_required', false);
    }

    public function down(): void
    {
        $this->migrator->delete('project.state_required');
    }
};
