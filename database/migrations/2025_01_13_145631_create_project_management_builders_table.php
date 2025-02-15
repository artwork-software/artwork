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
        Schema::create('project_management_builders', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->smallInteger('order');
            $table->boolean('is_active')->default(true);
            $table->string('type');
            $table->boolean('deletable')->default(true);
            // component_id
            $table->foreignId('component_id')->nullable()
                ->references('id')
                ->on('components')
                ->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_management_builders');
    }
};
