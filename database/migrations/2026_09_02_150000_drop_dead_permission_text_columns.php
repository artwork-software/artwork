<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * name_de und tooltipText wurden nie gelesen (Frontend übersetzt translation_key/tooltipKey über lang/*.json).
 * Texte kommen jetzt aus dem Rechte-Katalog. `checked` bleibt (Standard-Recht für neue Personen).
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['permissions', 'roles'] as $table) {
            Schema::table($table, function (Blueprint $blueprint) use ($table): void {
                foreach (['name_de', 'tooltipText'] as $column) {
                    if (Schema::hasColumn($table, $column)) {
                        $blueprint->dropColumn($column);
                    }
                }
            });
        }
    }

    public function down(): void
    {
        foreach (['permissions', 'roles'] as $table) {
            Schema::table($table, function (Blueprint $blueprint) use ($table): void {
                if (!Schema::hasColumn($table, 'name_de')) {
                    $blueprint->string('name_de')->nullable();
                }
                if (!Schema::hasColumn($table, 'tooltipText')) {
                    $blueprint->longText('tooltipText')->nullable();
                }
            });
        }
    }
};
