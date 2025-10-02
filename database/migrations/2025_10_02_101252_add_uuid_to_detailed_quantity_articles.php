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
        Schema::table('inventory_detailed_quantity_articles', function (Blueprint $table) {
            $table->uuid('type_number')->after('id')->unique();
        });
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
