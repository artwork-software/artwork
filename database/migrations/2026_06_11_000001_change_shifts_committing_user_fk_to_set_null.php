<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * shifts.committing_user_id stand auf ON DELETE CASCADE: Das endgültige Löschen
 * eines Users (Papierkorb-Purge) löschte damit ALLE jemals von ihm festgeschriebenen
 * Schichten hart mit — inklusive shift_workers/shifts_qualifications über deren
 * Folge-Kaskaden, ohne Anwendungslogik, Logs oder Notifications.
 * Die Spalte ist nullable; SET NULL ist das korrekte Verhalten (wie bei
 * event_id, project_id und room_id).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shifts', function (Blueprint $table): void {
            $table->dropForeign('shifts_committing_user_id_foreign');
            $table->foreign('committing_user_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('shifts', function (Blueprint $table): void {
            $table->dropForeign('shifts_committing_user_id_foreign');
            $table->foreign('committing_user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
        });
    }
};
