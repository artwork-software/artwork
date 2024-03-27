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
        Schema::create('events', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->string('eventName')->nullable();
            $table->longText('description')->nullable();
            $table->timestamp('start_time')->nullable();
            $table->timestamp('end_time')->nullable();
            $table->boolean('occupancy_option')->default(false);
            $table->boolean('audience')->default(false);
            $table->boolean('is_loud')->default(false);
            $table->boolean('allDay')->default(false);
            $table->unsignedBigInteger('event_type_id');
            $table->unsignedBigInteger('room_id')->nullable();
            $table->unsignedBigInteger('declined_room_id')->nullable();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('project_id')->nullable();
            $table->boolean('is_series')->default(false);
            $table->unsignedBigInteger('series_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('event_migration');
    }
};
