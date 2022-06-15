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
    public function up()
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('description')->nullable(true);
            $table->timestamp('start_time')->nullable(true);
            $table->timestamp('end_time')->nullable(true);
            $table->boolean('occupancy_option')->default(false)->nullable(true);;
            $table->boolean('audience')->default(false)->nullable(true);;
            $table->boolean('is_loud')->default(false)->nullable(true);
            $table->unsignedBigInteger('event_type_id');
            $table->unsignedBigInteger('room_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('project_id')->nullable(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('event_migration');
    }
};
