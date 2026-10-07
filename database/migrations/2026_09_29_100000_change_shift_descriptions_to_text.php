<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Schichtnotizen enthalten in der Praxis ganze Technik-Rider (Bühne, Ton, Licht). Mit varchar(255)
 * scheiterte das Speichern mit SQLSTATE 1406. Vorlagen-Schichten werden in Schichten übernommen
 * und brauchen deshalb dieselbe Länge.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shifts', function (Blueprint $table): void {
            $table->text('description')->nullable()->default(null)->change();
        });

        Schema::table('preset_shifts', function (Blueprint $table): void {
            $table->text('description')->nullable()->default(null)->change();
        });
    }

    public function down(): void
    {
        // Längere Notizen vorher auf 255 Zeichen kürzen, sonst scheitert change() im strict mode (SQLSTATE 1406).
        // LENGTH zählt in MySQL Bytes: trifft auch kürzere Mehrbyte-Texte, SUBSTR lässt die unverändert.
        foreach (['shifts', 'preset_shifts'] as $tableName) {
            DB::table($tableName)
                ->whereRaw('LENGTH(description) > 255')
                ->update(['description' => DB::raw('SUBSTR(description, 1, 255)')]);
        }

        Schema::table('shifts', function (Blueprint $table): void {
            $table->string('description')->nullable()->default(null)->change();
        });

        Schema::table('preset_shifts', function (Blueprint $table): void {
            $table->string('description')->nullable()->default(null)->change();
        });
    }
};
