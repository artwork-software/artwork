<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Abwesenheiten aus dem Status-Dropdown trugen früher den Status als Kommentar (z. B. „FREE_WORK“).
     * Wurde so ein Eintrag im Verfügbarkeitskalender auf „Verfügbar“ umgestellt, wanderte der Kommentar in
     * die neue Verfügbarkeit; die Dienstplan-Zelle zeigt dann „Verfügbar, „FREE_WORK““. Die Bereinigung vom
     * 12.11. erfasste nur die Abwesenheiten und nur exakte Treffer; hier folgen die Verfügbarkeiten und
     * Kommentare mit Leerzeichen drumherum in beiden Tabellen.
     */
    public function up(): void
    {
        $statusTypes = ['OFF_WORK', 'NOT_AVAILABLE', 'FREE_WORK', 'AVAILABLE'];

        foreach (['availabilities', 'vacations'] as $table) {
            DB::table($table)
                ->whereNotNull('comment')
                ->whereIn(DB::raw('TRIM(comment)'), $statusTypes)
                ->update(['comment' => null]);
        }
    }

    public function down(): void
    {
        // Die Kommentare wiederholten nur den Status und werden nicht wiederhergestellt.
    }
};
