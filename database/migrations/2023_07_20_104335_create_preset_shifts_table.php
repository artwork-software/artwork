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
        Schema::create('preset_shifts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('shift_preset_id');
            $table->time('start');
            $table->time('end');
            $table->integer('break_minutes')->default(0);
            $table->unsignedBigInteger('craft_id');
            $table->integer('number_employees')->default(0);
            $table->integer('number_masters')->default(0);
            $table->string('description')->nullable();
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
        Schema::dropIfExists('preset_shifts');
    }
};
