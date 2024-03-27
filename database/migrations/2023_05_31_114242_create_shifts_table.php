<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('shifts', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('event_id');
            $table->time('start');
            $table->time('end');
            $table->integer('break_minutes')->default(0);
            $table->unsignedBigInteger('craft_id');
            $table->integer('number_employees')->default(0);
            $table->integer('number_masters')->default(0);
            $table->string('description')->nullable();
            $table->boolean('is_committed')->default(false);
            $table->string('shift_uuid')->nullable();
            $table->date('event_start_day')->nullable();
            $table->date('event_end_day')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('shifts');
    }
};
