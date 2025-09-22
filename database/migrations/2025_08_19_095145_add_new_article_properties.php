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
        Schema::table('inventory_article_properties', function (Blueprint $table) {
            $table->boolean('across_articles')->default(false);
            $table->boolean('individual_value')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory_article_properties', function (Blueprint $table) {
            $table->dropColumn(['across_articles', 'individual_value']);
        });
    }
};
