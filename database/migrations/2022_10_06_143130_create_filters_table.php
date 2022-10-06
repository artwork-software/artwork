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
        Schema::create('filters', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('user_id');
            $table->boolean('isLoud')->default(false)->nullable(true);
            $table->boolean('isNotLoud')->default(false)->nullable(true);;
            $table->boolean('hasAudience')->default(false)->nullable(true);;
            $table->boolean('hasNoAudience')->default(false)->nullable(true);;
            $table->boolean('adjoiningNoAudience')->default(false)->nullable(true);;
            $table->boolean('adjoiningNotLoud')->default(false)->nullable(true);;
            $table->boolean('allDayFree')->default(false)->nullable(true);;
            $table->boolean('showAdjoiningRooms')->default(false)->nullable(true);;
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
        Schema::dropIfExists('filters');
    }
};
