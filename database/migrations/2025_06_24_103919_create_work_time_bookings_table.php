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
        Schema::create('work_time_bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('cascade');
            $table->string('name')->nullable();
            $table->text('comment')->nullable();
            $table->date('booking_day')->nullable();
            $table->integer('booking_weekday')->nullable();
            $table->integer('wanted_working_hours')->default(0);
            $table->integer('worked_hours')->default(0);
            $table->boolean('is_special_day')->default(false);
            $table->integer('nightly_working_hours')->default(0);
            $table->integer('work_time_balance_change')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('work_time_bookings');
    }
};
