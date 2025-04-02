<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_articles', function (Blueprint $table) {

            if (!Schema::hasColumn('inventory_articles', 'deleted_at')) {
                $table->softDeletes();
            }

            // Drop the existing foreign key only, don't redefine the column
            if (Schema::hasColumn('inventory_articles', 'inventory_sub_category_id')) {
                $table->dropForeign(['inventory_sub_category_id']);
            }

            $table->foreign('inventory_sub_category_id')
                ->references('id')
                ->on('inventory_sub_categories')
                ->nullOnDelete();
        });

        Schema::table('inventory_detailed_quantity_articles', function (Blueprint $table) {

            if (Schema::hasColumn('inventory_detailed_quantity_articles', 'inv_det_qty_art_inv_art_fk')) {
                $table->dropForeign(['inv_det_qty_art_inv_art_fk']);
            }

            $table->foreign('inventory_article_id')
                ->references('id')
                ->on('inventory_articles')
                ->onDelete('cascade')
                ->name('inv_det_qty_art_inv_art_fk');
        });
    }

    public function down(): void
    {
        Schema::table('inventory_articles', function (Blueprint $table) {
            $table->dropSoftDeletes();
            if (Schema::hasColumn('inventory_articles', 'inventory_sub_category_id')) {
                $table->dropForeign('inventory_sub_category_id');
            }
        });

        Schema::table('inventory_detailed_quantity_articles', function (Blueprint $table) {
            if (Schema::hasColumn('inventory_detailed_quantity_articles', 'inventory_article_id')) {
                $table->dropForeign(['inventory_article_id']);
            }
        });
    }
};
