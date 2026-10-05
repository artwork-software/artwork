<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('general.always_direct_booking', false);
    }

    public function down(): void
    {
        $this->migrator->delete('general.always_direct_booking');
    }
};
