<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Inventarnummern wurden als max+1 über die vorhandenen Artikel vergeben. Nach dem endgültigen
 * Löschen des Artikels mit der höchsten Nummer bekam der nächste neue Artikel dieselbe Nummer –
 * vorhandene Etiketten verwiesen dann auf den falschen Artikel. Ein Zähler merkt sich die zuletzt
 * vergebene Nummer dauerhaft.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_number_counter', function (Blueprint $table): void {
            $table->unsignedTinyInteger('id')->primary();
            $table->unsignedBigInteger('last_number')->default(0);
        });

        $currentMax = (int) DB::table('inventory_articles')
            ->max(DB::raw('CAST(inventory_number AS UNSIGNED)'));

        DB::table('inventory_number_counter')->insert(['id' => 1, 'last_number' => $currentMax]);
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_number_counter');
    }
};
