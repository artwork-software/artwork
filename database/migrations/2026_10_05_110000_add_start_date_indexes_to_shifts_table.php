<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Wochenstatus, Workflow-Status, Freigabe-Anfragen und Regelprüfungen suchen Schichten nach
 * Zeitraum (optional je Gewerk) ohne Raum. Der vorhandene Index (room_id, start_date, end_date)
 * greift dafür nicht, die Abfragen liefen als Full-Table-Scan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shifts', function (Blueprint $table): void {
            if (!Schema::hasIndex('shifts', 'shifts_start_date_index')) {
                $table->index('start_date', 'shifts_start_date_index');
            }
            if (!Schema::hasIndex('shifts', 'shifts_craft_start_date_index')) {
                $table->index(['craft_id', 'start_date'], 'shifts_craft_start_date_index');
            }
        });
    }

    public function down(): void
    {
        Schema::table('shifts', function (Blueprint $table): void {
            $table->dropIndex('shifts_start_date_index');
            $table->dropIndex('shifts_craft_start_date_index');
        });
    }
};
