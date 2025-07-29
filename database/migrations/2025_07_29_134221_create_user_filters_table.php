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
        Schema::create('user_filters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('filter_type')->default('calendar_filter');
            $table->json('event_type_ids')->nullable();
            $table->json('room_ids')->nullable();
            $table->json('area_ids')->nullable();
            $table->json('room_attribute_ids')->nullable();
            $table->json('room_category_ids')->nullable();
            $table->json('event_property_ids')->nullable();
            $table->json('craft_ids')->nullable();
            $table->unique(['user_id', 'filter_type'], 'user_filter_unique');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_filters');
    }
};
