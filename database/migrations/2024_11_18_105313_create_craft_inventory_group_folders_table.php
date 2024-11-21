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
        Schema::create('craft_inventory_group_folders', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->smallInteger('order')->default(0);
            $table->foreignId('craft_inventory_group_id')
                ->references('id')
                ->on('craft_inventory_groups')
                ->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('craft_inventory_group_folders');
    }
};
