<?php

/**
 * Stichtag der Rückgabe-Erfassung einmalig festschreiben (ExternalIssue::returnTrackingSince()).
 * Der früheste Erinnerungs-Stempel ist der Backfill vom Einspielen der Rückgabe-Erfassung (08/2026) –
 * zur Laufzeit abgeleitet würde der Stichtag aber nach vorn springen, sobald die Altfälle bereinigt
 * sind oder ein Haus ohne Altbestand seine erste echte Erinnerung bekommt.
 */

use Illuminate\Support\Facades\DB;
use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration {
    private const RELEASE_DATE = '2026-08-13';

    public function up(): void
    {
        if ($this->migrator->exists('inventory.return_tracking_since')) {
            return;
        }

        // Nur Backfill-Stempel (gesetzt nach dem Rückgabedatum, beim Einspielen der Erfassung); echte
        // Erinnerungen gehen am Rückgabedatum raus und dürfen den Stichtag nicht bestimmen
        // Backfill-Stempel: nach dem Rückgabedatum UND deutlich nach der Anlage gesetzt – eine
        // nachträglich erfasste (rückdatierte) Ausgabe wird am Folgetag erinnert und zählt nicht
        $firstBackfillStamp = DB::table('external_issues')
            ->whereNotNull('return_notification_sent_at')
            ->whereRaw('DATE(return_notification_sent_at) > return_date')
            ->whereRaw('created_at < return_notification_sent_at - INTERVAL 1 DAY')
            ->min('return_notification_sent_at');
        $since = $firstBackfillStamp !== null ? substr((string) $firstBackfillStamp, 0, 10) : self::RELEASE_DATE;

        $this->migrator->add('inventory.return_tracking_since', max($since, self::RELEASE_DATE));
    }

    public function down(): void
    {
        $this->migrator->deleteIfExists('inventory.return_tracking_since');
    }
};
