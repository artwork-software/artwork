<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Der Schichtverlauf filtert Activities nach log_name + subject_type und sortiert
     * absteigend nach created_at (mit LIMIT/Pagination). Bisher konnte MySQL für die
     * Sortierung keinen Index nutzen ("Using filesort") und musste die gesamte
     * Treffermenge im Speicher sortieren. Dieser zusammengesetzte Index erlaubt einen
     * geordneten Zugriff und damit frühen Abbruch beim LIMIT.
     */
    public function up(): void
    {
        Schema::table('activity_log', function (Blueprint $table): void {
            $table->index(
                ['log_name', 'subject_type', 'created_at'],
                'activity_log_log_name_subject_type_created_at_index'
            );
        });
    }

    public function down(): void
    {
        Schema::table('activity_log', function (Blueprint $table): void {
            $table->dropIndex('activity_log_log_name_subject_type_created_at_index');
        });
    }
};
