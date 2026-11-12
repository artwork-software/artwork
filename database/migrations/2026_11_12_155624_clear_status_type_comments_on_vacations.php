<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Das Status-Dropdown im Dienstplan schrieb den Status selbst als Kommentar in die Abwesenheit
     * (z. B. „NOT_AVAILABLE“). Dienstplan-Zelle und Freigabe-Anfrage zeigten das als Kommentar an.
     * Neue Einträge haben keinen Kommentar mehr; hier werden die vorhandenen bereinigt.
     */
    public function up(): void
    {
        DB::table('vacations')
            ->whereIn('comment', ['OFF_WORK', 'NOT_AVAILABLE', 'FREE_WORK', 'AVAILABLE'])
            ->update(['comment' => null]);
    }

    public function down(): void
    {
        // Die Kommentare wiederholten nur den Status und werden nicht wiederhergestellt.
    }
};
