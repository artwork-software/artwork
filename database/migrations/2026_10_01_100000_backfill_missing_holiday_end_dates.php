<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Feiertage, die beim Bearbeiten ohne Enddatum gespeichert wurden, ließen den Kalender mit einem
 * Fehler abbrechen. Solche Einträge enden am Starttag; neue Einträge setzt das Holiday-Model selbst.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('holidays')) {
            return;
        }

        DB::table('holidays')
            ->whereNull('end_date')
            ->update(['end_date' => DB::raw('`date`')]);
    }

    public function down(): void
    {
        // Bewusst keine Rückabwicklung: ein fehlendes Enddatum war nie fachlich gewollt.
    }
};
