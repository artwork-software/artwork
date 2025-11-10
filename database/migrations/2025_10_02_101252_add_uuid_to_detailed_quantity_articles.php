<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('inventory_detailed_quantity_articles', 'type_number')) {
            Schema::table('inventory_detailed_quantity_articles', function (Blueprint $table) {
                $table->uuid('type_number')->nullable()->after('id');
            });

            DB::statement('UPDATE inventory_detailed_quantity_articles SET type_number = UUID() WHERE type_number IS NULL OR type_number = ""');

            Schema::table('inventory_detailed_quantity_articles', function (Blueprint $table) {
                $table->unique('type_number', 'inventory_detailed_quantity_articles_type_number_unique');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory_detailed_quantity_articles', function (Blueprint $table) {
            $table->dropColumn('type_number');
        });
    }
};
