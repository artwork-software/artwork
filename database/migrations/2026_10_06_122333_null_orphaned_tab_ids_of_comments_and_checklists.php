<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Kommentare und Checklisten aus früher gelöschten Tabs behielten ihre tab_id. Seit „Alle Kommentare“/
 * „Alle Checklisten“ auf sichtbare Tabs eingeschränkt sind, wären sie für niemanden mehr sichtbar —
 * bisher sah sie jede Person mit Projektzugriff. tab_id = null erhält genau diese Sichtbarkeit.
 * Dateien bleiben unverändert: „Alle Dokumente“ blendete Dateien gelöschter Tabs schon immer aus.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['comments', 'checklists'] as $table) {
            if (!Schema::hasTable($table) || !Schema::hasColumn($table, 'tab_id')) {
                continue;
            }

            DB::table($table)
                ->whereNotNull('tab_id')
                ->whereNotExists(function (Builder $query) use ($table): void {
                    $query->selectRaw('1')
                        ->from('project_tabs')
                        ->whereColumn('project_tabs.id', $table . '.tab_id');
                })
                ->update(['tab_id' => null]);
        }
    }

    public function down(): void
    {
        // Datenreparatur, nicht umkehrbar
    }
};
