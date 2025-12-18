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
        Schema::create('shift_preset_group_assignments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('shift_preset_group_id');
            $table->unsignedBigInteger('single_shift_preset_id');
            $table->timestamps();

            $table->foreign('shift_preset_group_id')->references('id')->on('shift_preset_groups')->onDelete('cascade');
            $table->foreign('single_shift_preset_id')->references('id')->on('single_shift_presets')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shift_preset_group_assignments');
    }
};
