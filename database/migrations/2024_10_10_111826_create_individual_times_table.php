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
        Schema::create('individual_times', function (Blueprint $table) {
            $table->id();
            $table->morphs('timeable');
            $table->string('title')->nullable();
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('full_day')->default(false);
            $table->integer('working_time_minutes')->nullable();
            $table->timestamps();

            $table->index(['timeable_id', 'timeable_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('individual_times');
    }
};
