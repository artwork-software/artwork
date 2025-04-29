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
            $table->foreignId('inventory_article_status_id')
                ->nullable()
                ->constrained('inventory_article_statuses')
                ->name('inv_det_art_stat_fk')
                ->cascadeOnDelete()
                ->after('inventory_article_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory_detailed_quantity_articles', function (Blueprint $table) {
            $table->dropForeign('inv_det_art_stat_fk');
            $table->dropColumn('inventory_article_status_id');
        });
    }
};
