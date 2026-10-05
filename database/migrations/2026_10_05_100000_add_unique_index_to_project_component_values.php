<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Je Projekt und Komponente gibt es genau einen Wert. Parallele Autosaves konnten beim ersten
 * Speichern zwei Zeilen anlegen; gelesen und weiter beschrieben wurde danach immer die älteste.
 * Die übrigen Zeilen sind verwaist und werden entfernt, dann sichert ein Unique-Index ab.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('project_component_values')) {
            return;
        }

        $duplicates = DB::table('project_component_values')
            ->select('project_id', 'component_id', DB::raw('MIN(id) as keep_id'))
            ->groupBy('project_id', 'component_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicates as $duplicate) {
            DB::table('project_component_values')
                ->where('project_id', $duplicate->project_id)
                ->where('component_id', $duplicate->component_id)
                ->where('id', '!=', $duplicate->keep_id)
                ->delete();
        }

        Schema::table('project_component_values', function (Blueprint $table): void {
            $table->unique(['project_id', 'component_id'], 'project_component_values_project_component_unique');
        });
    }

    public function down(): void
    {
        Schema::table('project_component_values', function (Blueprint $table): void {
            $table->dropUnique('project_component_values_project_component_unique');
        });
    }
};
