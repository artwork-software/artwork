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
        Schema::create('availabilities', function (Blueprint $table) {
            $table->id();
            $table->morphs('available');
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->date('date');
            $table->boolean('full_day')->default(false);
            $table->string('comment', 20)->nullable();
            $table->boolean('is_series')->default(false);
            $table->integer('series_id')->nullable();
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
        Schema::dropIfExists('availabilities');
    }
};
