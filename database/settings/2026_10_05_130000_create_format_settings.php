<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration {
    public function up(): void
    {
        // Standard = bisheriges Verhalten (fest de-DE, EUR, d.m.Y)
        $this->migrator->add('format.number_locale', 'de-DE');
        $this->migrator->add('format.currency', 'EUR');
        $this->migrator->add('format.date_format', 'd.m.Y');
    }

    public function down(): void
    {
        $this->migrator->delete('format.number_locale');
        $this->migrator->delete('format.currency');
        $this->migrator->delete('format.date_format');
    }
};
