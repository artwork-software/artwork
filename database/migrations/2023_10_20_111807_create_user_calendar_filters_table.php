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
        Schema::create('user_calendar_filters', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->boolean('is_loud')->default(false);
            $table->boolean('is_not_loud')->default(false);
            $table->boolean('adjoining_not_loud')->default(false);
            $table->boolean('has_audience')->default(false);
            $table->boolean('has_no_audience')->default(false);
            $table->boolean('adjoining_no_audience')->default(false);
            $table->boolean('show_free_rooms')->default(false);
            $table->boolean('show_adjoining_rooms')->default(false);
            $table->boolean('all_day_free')->default(false);
            $table->json('event_types')->nullable();
            $table->json('rooms')->nullable();
            $table->json('areas')->nullable();
            $table->json('room_attributes')->nullable();
            $table->json('room_categories')->nullable();
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
        Schema::dropIfExists('user_calendar_filters');
    }
};
