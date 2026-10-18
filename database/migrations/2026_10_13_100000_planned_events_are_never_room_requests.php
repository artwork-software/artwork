<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Geplante Termine kennen keine Raumanfrage mehr (Entscheidung 13.10.2026): Altbestand mit
 * occupancy_option wird zu fest geplanten Terminen. Raumadmins wurden für geplante Termine ohnehin
 * nie benachrichtigt, es gehen keine offenen Anfragen verloren.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('events')
            ->where('is_planning', true)
            ->where('occupancy_option', true)
            ->update(['occupancy_option' => false]);
    }

    public function down(): void
    {
        // Nicht umkehrbar: der frühere Anfrage-Status ist nach der Übernahme nicht mehr bekannt.
    }
};
