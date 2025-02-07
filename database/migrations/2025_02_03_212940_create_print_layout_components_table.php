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
        Schema::create('print_layout_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_print_layout_id')->references('id')->on('project_print_layouts');
            $table->foreignId('component_id')->references('id')->on('components');
            $table->string('type')->default('body');
            $table->integer('position');
            $table->integer('row');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('print_layout_components');
    }
};
