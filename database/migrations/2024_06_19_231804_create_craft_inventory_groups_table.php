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
        Schema::create('craft_inventory_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('craft_inventory_category_id')->constrained('craft_inventory_categories')->cascadeOnDelete();
            $table->text('name');
            $table->smallInteger('order');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('craft_inventory_groups');
    }
};
