<?php

use Illuminate\Support\Facades\DB;
use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // Bestandsinstanzen, in denen granulare Rechte bereits aktiv sind, haben
        // ihre Default-Rechte schon verteilt. Das Einmal-Flag muss dort auf true
        // starten, sonst würde das nächste Off→On die Defaults erneut verteilen
        // und individuell entzogene Rechte zurückbringen.
        $alreadyEnabled = $this->granularPermissionsAlreadyEnabled();

        $this->migrator->add('shift-settings.granular_defaults_granted', $alreadyEnabled);
    }

    public function down(): void
    {
        $this->migrator->delete('shift-settings.granular_defaults_granted');
    }

    /**
     * Rohen Settings-Wert lesen (nicht über die Settings-Klasse, da diese die
     * noch nicht existierende Property erwarten und MissingSettings werfen würde).
     */
    private function granularPermissionsAlreadyEnabled(): bool
    {
        $payload = DB::table('settings')
            ->where('group', 'shift-settings')
            ->where('name', 'granular_permissions_enabled')
            ->value('payload');

        return $payload !== null && json_decode($payload, true) === true;
    }
};
