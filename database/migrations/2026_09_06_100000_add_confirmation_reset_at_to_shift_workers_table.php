<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Wird eine Zusage durch eine Zeitaenderung zurueckgesetzt, bleibt das bisher unsichtbar
 * (alle confirmation_*-Felder werden genullt). Der Zeitstempel erlaubt der Einsatzplan-Karte
 * den Hinweis "Zeit geaendert - bitte erneut zusagen".
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('shift_workers') || Schema::hasColumn('shift_workers', 'confirmation_reset_at')) {
            return;
        }

        Schema::table('shift_workers', function (Blueprint $table): void {
            $table->timestamp('confirmation_reset_at')->nullable()->after('confirmation_comment');
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('shift_workers') && Schema::hasColumn('shift_workers', 'confirmation_reset_at')) {
            Schema::table('shift_workers', function (Blueprint $table): void {
                $table->dropColumn('confirmation_reset_at');
            });
        }
    }
};
