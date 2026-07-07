<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration {
    public function up(): void
    {
        $this->migrator->add('shift-settings.allow_shift_overbooking', false);
    }

    public function down(): void
    {
        $this->migrator->delete('shift-settings.allow_shift_overbooking');
    }
};
