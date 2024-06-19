<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('project.attributes', true);
        $this->migrator->add('project.state', true);
        $this->migrator->add('project.managers', true);
        $this->migrator->add('project.cost_center', true);
        $this->migrator->add('project.budget_deadline', true);
    }
};
