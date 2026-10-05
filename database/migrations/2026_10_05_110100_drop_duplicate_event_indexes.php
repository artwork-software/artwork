<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * events trägt vier Indizes doppelt (gleiche Spalte, zwei Namen). Jeder Doppelindex kostet bei
 * jedem Schreiben von Terminen ohne Nutzen. Entfernt wird nur, wenn der gleichwertige Zwilling
 * existiert; die Fremdschlüssel-Indizes bleiben unangetastet.
 */
return new class extends Migration
{
    /**
     * @var array<string, string> zu entfernender Index => gleichwertiger, verbleibender Index
     */
    private const DUPLICATES = [
        'idx_events_start' => 'events_start_time_index',
        'idx_events_end' => 'events_end_time_index',
        'idx_events_type' => 'events_event_type_id_foreign',
        'idx_events_status' => 'events_event_status_id_foreign',
    ];

    public function up(): void
    {
        foreach (self::DUPLICATES as $duplicate => $twin) {
            if (Schema::hasIndex('events', $duplicate) && Schema::hasIndex('events', $twin)) {
                Schema::table('events', function (Blueprint $table) use ($duplicate): void {
                    $table->dropIndex($duplicate);
                });
            }
        }
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table): void {
            $table->index('start_time', 'idx_events_start');
            $table->index('end_time', 'idx_events_end');
            $table->index('event_type_id', 'idx_events_type');
            $table->index('event_status_id', 'idx_events_status');
        });
    }
};
