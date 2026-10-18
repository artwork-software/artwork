<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Meldungen mit ungültigem JSON in data lassen sich nirgends anzeigen. Im Strict-Mode bricht MariaDB
 * jede schreibende Anweisung mit JSON-Bedingung an einer solchen Zeile ab (Fehler 4038) – die
 * folgenden Release-Migrationen ebenso wie das Löschen von Raumanfrage-Meldungen zur Laufzeit.
 * Läuft vor 2026_10_05_110200 (generierte Spalte groupType).
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('notifications')->whereRaw('NOT JSON_VALID(data)')->delete();
    }

    public function down(): void
    {
        // Datenreparatur, nicht umkehrbar
    }
};
