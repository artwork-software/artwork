<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Ref 1.41: global, drag & drop sortable order for inventory article properties.
     */
    public function up(): void
    {
        Schema::table('inventory_article_properties', function (Blueprint $table) {
            $table->integer('order')->default(0)->after('individual_value');
        });

        // Seed a stable initial order from the current id sequence.
        foreach (DB::table('inventory_article_properties')->orderBy('id')->pluck('id') as $index => $id) {
            DB::table('inventory_article_properties')->where('id', $id)->update(['order' => $index]);
        }
    }

    public function down(): void
    {
        Schema::table('inventory_article_properties', function (Blueprint $table) {
            $table->dropColumn('order');
        });
    }
};
