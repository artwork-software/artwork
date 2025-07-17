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
        Schema::create('user_work_time_patterns', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->time('monday')->default('00:00:00');
            $table->time('tuesday')->default('00:00:00');
            $table->time('wednesday')->default('00:00:00');
            $table->time('thursday')->default('00:00:00');
            $table->time('friday')->default('00:00:00');
            $table->time('saturday')->default('00:00:00');
            $table->time('sunday')->default('00:00:00');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_work_time_patterns');
    }
};
