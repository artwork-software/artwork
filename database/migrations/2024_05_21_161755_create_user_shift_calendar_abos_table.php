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
        Schema::create('user_shift_calendar_abos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('calendar_abo_id');
            $table->boolean('date_range')->default(false);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->boolean('specific_event_types')->default(false);
            $table->json('event_types')->nullable();
            $table->boolean('enable_notification')->default(false);
            $table->integer('notification_time')->nullable();
            $table->enum('notification_time_unit', ['minutes', 'hours', 'days'])->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_shift_calendar_abos');
    }
};
