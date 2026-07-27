<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Plan/Ist-Dimension (BI-Ausbau Phase 3): bi_project_data und bi_event_data
 * bekommen je Projekt einen zweiten, parallelen Plan-Datensatz (scope 'plan');
 * Snapshots frieren wahlweise Plan oder Ist ein. Bestand = 'actual'.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('bi_project_data', 'scope')) {
            Schema::table('bi_project_data', function (Blueprint $table): void {
                $table->enum('scope', ['actual', 'plan'])->default('actual')->after('project_id');
                // Herkunft eines per Vorschlags-Button übernommenen Umsatzwerts
                $table->string('revenue_source')->nullable()->after('revenue_not_applicable');
                $table->timestamp('revenue_source_synced_at')->nullable()->after('revenue_source');
            });

            // Neuen Composite-Unique VOR dem Drop anlegen: die FK auf project_id braucht
            // durchgehend einen Index mit project_id als führender Spalte.
            Schema::table('bi_project_data', function (Blueprint $table): void {
                $table->unique(['project_id', 'scope'], 'bi_project_data_project_scope_unique');
                $table->dropUnique('bi_project_data_project_id_unique');
            });
        }

        if (!Schema::hasColumn('bi_event_data', 'scope')) {
            Schema::table('bi_event_data', function (Blueprint $table): void {
                $table->enum('scope', ['actual', 'plan'])->default('actual')->after('event_id');
            });

            Schema::table('bi_event_data', function (Blueprint $table): void {
                $table->unique(['project_id', 'event_id', 'scope'], 'bi_event_data_project_event_scope_unique');
                $table->dropUnique('bi_event_data_project_id_event_id_unique');
            });
        }

        if (!Schema::hasColumn('bi_snapshots', 'scope')) {
            Schema::table('bi_snapshots', function (Blueprint $table): void {
                $table->enum('scope', ['actual', 'plan'])->default('actual')->after('project_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('bi_project_data', 'scope')) {
            Schema::table('bi_project_data', function (Blueprint $table): void {
                $table->unique(['project_id'], 'bi_project_data_project_id_unique');
                $table->dropUnique('bi_project_data_project_scope_unique');
                $table->dropColumn(['scope', 'revenue_source', 'revenue_source_synced_at']);
            });
        }

        if (Schema::hasColumn('bi_event_data', 'scope')) {
            Schema::table('bi_event_data', function (Blueprint $table): void {
                $table->unique(['project_id', 'event_id'], 'bi_event_data_project_id_event_id_unique');
                $table->dropUnique('bi_event_data_project_event_scope_unique');
                $table->dropColumn('scope');
            });
        }

        if (Schema::hasColumn('bi_snapshots', 'scope')) {
            Schema::table('bi_snapshots', function (Blueprint $table): void {
                $table->dropColumn('scope');
            });
        }
    }
};
