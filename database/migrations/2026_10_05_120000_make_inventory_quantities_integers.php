<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Inventarmengen sind Stückzahlen. Die Spalten waren double(8,2) bzw. varchar, Validierung und
 * Model-Casts gingen aber von ganzen Zahlen aus: 2,5 wurde gespeichert, per (int) abgeschnitten
 * und per SQL-SUM wieder mitgezählt. Bestandswerte werden kaufmännisch gerundet, nicht-numerische
 * Statusmengen auf 0 gesetzt.
 */
return new class extends Migration
{
    /** Obergrenze der Zielspalte unsigned int */
    private const MAX_UNSIGNED_INT = 4294967295;

    public function up(): void
    {
        $this->roundQuantities('inventory_articles');
        $this->roundQuantities('inventory_detailed_quantity_articles');
        $this->normalizeStatusValues('inventory_article_status_values');

        Schema::table('inventory_articles', function (Blueprint $table): void {
            $table->unsignedInteger('quantity')->default(0)->change();
        });
        Schema::table('inventory_detailed_quantity_articles', function (Blueprint $table): void {
            $table->unsignedInteger('quantity')->default(0)->change();
        });
        Schema::table('inventory_article_status_values', function (Blueprint $table): void {
            $table->unsignedInteger('value')->default(0)->change();
        });
    }

    /**
     * Mengen kaufmännisch runden. ROUND() direkt auf DOUBLE rundet in MariaDB zur geraden Zahl
     * (0,5 → 0, 2,5 → 2) – erst als DECIMAL ist das Runden exakt und halb-auf.
     * Öffentlich, damit der Test die Umrechnung auf einer Tabelle mit altem Spaltentyp prüfen kann.
     */
    public function roundQuantities(string $table): void
    {
        DB::table($table)->update([
            'quantity' => DB::raw('ROUND(GREATEST(CAST(quantity AS DECIMAL(65,4)), 0))'),
        ]);
    }

    /**
     * Statusmengen (varchar) in ganze Zahlen überführen:
     * - deutsche Tausenderpunkte („1.000“, „12.500“, „1.000,5“) zählen als Tausender (nicht mit führender 0:
     *   „0.500“ bleibt Dezimal). Statusmengen
     *   sind Stückzahlen; ein Wert mit genau drei Nachkommastellen hinter einem Punkt ist als
     *   Dezimalzahl unplausibel, die Oberfläche formatiert Mengen selbst mit Tausenderpunkt.
     * - sonst Komma oder Punkt als Dezimaltrenner, kaufmännisch gerundet (über DECIMAL, s. oben)
     * - Nicht-Numerisches wird 0, Negatives 0, zu Großes auf das Maximum der Zielspalte begrenzt
     */
    public function normalizeStatusValues(string $table): void
    {
        DB::table($table)
            ->whereRaw("TRIM(value) REGEXP '^-?[1-9][0-9]{0,2}([.][0-9]{3})+(,[0-9]+)?$'")
            ->update(['value' => DB::raw("REPLACE(REPLACE(TRIM(value), '.', ''), ',', '.')")]);
        DB::table($table)
            ->whereRaw("TRIM(value) NOT REGEXP '^-?[0-9]+([.,][0-9]+)?$'")
            ->update(['value' => '0']);
        DB::table($table)->update([
            'value' => DB::raw(
                "LEAST(ROUND(GREATEST(CAST(REPLACE(TRIM(value), ',', '.') AS DECIMAL(65,4)), 0)), "
                . self::MAX_UNSIGNED_INT . ')'
            ),
        ]);
    }

    public function down(): void
    {
        // Ursprünglicher Typ double(8,2): Blueprint::double() kennt seit Laravel 11 keine Genauigkeit mehr
        foreach (['inventory_articles', 'inventory_detailed_quantity_articles'] as $table) {
            DB::statement("ALTER TABLE `{$table}` MODIFY `quantity` DOUBLE(8,2) NOT NULL DEFAULT 0");
        }
        Schema::table('inventory_article_status_values', function (Blueprint $table): void {
            $table->string('value')->change();
        });
    }
};
