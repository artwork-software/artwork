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
            $table->foreignId('craft_inventory_group_folder_id')
                ->nullable()
                ->references('id')
                ->on('craft_inventory_group_folders')
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
