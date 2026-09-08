<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Der Feiertags-Import setzte "als Sondertag behandeln" bisher pauschal, auch für Schulferien.
 * Schulferien dürfen das Tagessoll nie senken. Bereits importierte Ferien-Einträge (OpenHolidays,
 * Name endet auf "ferien") werden entflaggt; manuell angelegte Einträge bleiben unberührt.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('holidays') || !Schema::hasColumn('holidays', 'treatAsSpecialDay')) {
            return;
        }

        DB::table('holidays')
            ->where('from_api', true)
            ->where('name', 'like', '%ferien')
            ->update(['treatAsSpecialDay' => false]);
    }

    public function down(): void
    {
        // Bewusst keine Rückabwicklung: das alte Verhalten war fachlich falsch.
    }
};
