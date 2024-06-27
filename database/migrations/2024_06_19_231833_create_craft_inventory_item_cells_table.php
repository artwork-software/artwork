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
        Schema::create('craft_inventory_item_cells', function (Blueprint $table) {
            $table->id();
            $table->foreignId('crafts_inventory_column_id')->constrained('crafts_inventory_columns')->cascadeOnDelete();
            $table->foreignId('craft_inventory_item_id')->constrained('craft_inventory_items')->cascadeOnDelete();
            $table->text('cell_value');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('craft_inventory_item_cells');
    }
};
