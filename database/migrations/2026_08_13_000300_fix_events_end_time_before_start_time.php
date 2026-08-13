<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Repariert Über-Mitternacht-Termine aus dem BulkBody, deren end_time durch den
     * end_day-Override VOR der start_time gespeichert wurde (z.B. 22:00–00:00 am
     * selben Tag). Solche Termine ergaben im Kalender-Mapping eine leere
     * CarbonPeriod und wurden in keiner Tageszelle angezeigt.
     *
     * Nur Zeilen, bei denen +1 Tag das Ende tatsächlich hinter den Start schiebt
     * (die Über-Mitternacht-Fälle) — anderweitig defekte Daten bleiben unberührt.
     */
    public function up(): void
    {
        DB::table('events')
            ->where('allDay', false)
            ->whereColumn('end_time', '<', 'start_time')
            ->whereRaw("DATE_ADD(end_time, INTERVAL 1 DAY) > start_time")
            ->update(['end_time' => DB::raw('DATE_ADD(end_time, INTERVAL 1 DAY)')]);
    }

    public function down(): void
    {
        // Datenreparatur — nicht umkehrbar.
    }
};
