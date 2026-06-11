<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Event-lose Schichten wurden ohne event_start_day/event_end_day angelegt.
 * Ruhezeit-Check (NotificationService) und Kollisionsberechnung (ShiftCountService)
 * lesen aber genau diese Felder — Carbon::parse(null) ergibt "jetzt" und macht
 * beide Prüfungen für diese Schichten unbrauchbar. Neue Schichten werden über
 * den saving-Hook im Shift-Model synchron gehalten; hier der Bestand.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('shifts')
            ->whereNull('event_id')
            ->update([
                'event_start_day' => DB::raw('start_date'),
                'event_end_day' => DB::raw('end_date'),
            ]);
    }

    public function down(): void
    {
        // Bewusst kein Rollback: der vorherige Zustand (NULL) war fehlerhaft.
    }
};
