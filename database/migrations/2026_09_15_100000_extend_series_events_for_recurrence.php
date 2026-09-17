<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('series_events', function (Blueprint $table): void {
            // Serienende ist jetzt entweder ein Datum ODER eine Anzahl Termine
            $table->dateTime('end_date')->nullable()->change();

            if (!Schema::hasColumn('series_events', 'start_date')) {
                // Anker der Serie (Datum des ersten Termins) – Basis für Ausrollen und Vorschau
                $table->date('start_date')->nullable()->after('frequency_id');
            }
            if (!Schema::hasColumn('series_events', 'weekdays')) {
                // ISO-Wochentage 1..7, nur bei wöchentlich/14-tägig; null = Wochentag des Ankers
                $table->json('weekdays')->nullable()->after('end_date');
            }
            if (!Schema::hasColumn('series_events', 'occurrence_count')) {
                // „Endet nach N Terminen" (inkl. erstem Termin), Alternative zu end_date
                $table->unsignedSmallInteger('occurrence_count')->nullable()->after('weekdays');
            }
        });

        Schema::table('events', function (Blueprint $table): void {
            if (!Schema::hasColumn('events', 'is_series_exception')) {
                // Einzeln angepasster Serientermin (Datum/Zeit/Raum weicht vom Muster ab):
                // wird beim Neu-Ausrollen und bei Zeit-Deltas auf die Serie nicht angefasst.
                $table->boolean('is_series_exception')->default(false)->after('is_series');
            }
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table): void {
            if (Schema::hasColumn('events', 'is_series_exception')) {
                $table->dropColumn('is_series_exception');
            }
        });

        Schema::table('series_events', function (Blueprint $table): void {
            foreach (['occurrence_count', 'weekdays', 'start_date'] as $column) {
                if (Schema::hasColumn('series_events', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
