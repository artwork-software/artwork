<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration {
    public function up(): void
    {
        $this->migrator->add('general.artist_residency_do_not_save_default', false);
        $this->migrator->add('general.artist_residency_daily_allowance_default', 0.0);
        $this->migrator->add('general.artist_residency_name_columns', [
            ['key' => 'name', 'enabled' => true],
            ['key' => 'first_name', 'enabled' => false],
            ['key' => 'last_name', 'enabled' => false],
        ]);
    }

    public function down(): void
    {
        $this->migrator->delete('general.artist_residency_do_not_save_default');
        $this->migrator->delete('general.artist_residency_daily_allowance_default');
        $this->migrator->delete('general.artist_residency_name_columns');
    }
};
