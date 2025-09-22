<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('inventory_category_property_values', function (Blueprint $table) {
            $table->unsignedInteger('position')->default(0)->after('value');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory_category_property_values', function (Blueprint $table) {
            $table->dropColumn('position');
        });
    }
};
