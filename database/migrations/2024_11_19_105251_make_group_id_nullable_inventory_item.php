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
        Schema::table('craft_inventory_items', function (Blueprint $table) {
            $table->dropForeign(['craft_inventory_group_id']);
            $table->foreignId('craft_inventory_group_id')
                ->change()
                ->nullable()
                ->default(null)
                ->references('id')
                ->on('craft_inventory_groups')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('craft_inventory_items', function (Blueprint $table) {
            //
        });
    }
};
