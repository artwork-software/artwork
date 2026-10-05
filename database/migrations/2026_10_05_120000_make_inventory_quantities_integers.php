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
    public function up(): void
    {
        DB::table('inventory_articles')->update(['quantity' => DB::raw('ROUND(GREATEST(quantity, 0))')]);
        DB::table('inventory_detailed_quantity_articles')
            ->update(['quantity' => DB::raw('ROUND(GREATEST(quantity, 0))')]);
        DB::table('inventory_article_status_values')
            ->whereRaw("TRIM(value) NOT REGEXP '^-?[0-9]+([.,][0-9]+)?$'")
            ->update(['value' => '0']);
        DB::table('inventory_article_status_values')
            ->update(['value' => DB::raw("ROUND(GREATEST(CAST(REPLACE(TRIM(value), ',', '.') AS DECIMAL(12,2)), 0))")]);

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

    public function down(): void
    {
        Schema::table('inventory_articles', function (Blueprint $table): void {
            $table->double('quantity', 8, 2)->default(0)->change();
        });
        Schema::table('inventory_detailed_quantity_articles', function (Blueprint $table): void {
            $table->double('quantity', 8, 2)->default(0)->change();
        });
        Schema::table('inventory_article_status_values', function (Blueprint $table): void {
            $table->string('value')->change();
        });
    }
};
