<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration {
    public function up(): void
    {
        // Instanzweiter Schalter: Externe Zugänge sind pro Haus bewusst freizuschalten.
        $this->migrator->add('external_access.enabled', false);
        // Tage vor Ablauf eines Tab-/CRM-Zugangs, an denen die Einladenden erinnert werden (0 = aus).
        $this->migrator->add('external_access.expiry_reminder_days', 3);
    }

    public function down(): void
    {
        $this->migrator->delete('external_access.enabled');
        $this->migrator->delete('external_access.expiry_reminder_days');
    }
};
