<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('general.playing_time_window_start', "");
        $this->migrator->add('general.playing_time_window_end', "");
        $this->migrator->add('general.shift_commit_workflow_enabled', false);
    }
};
