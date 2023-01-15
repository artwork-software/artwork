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
        Schema::create('sub_positions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->integer('position');
            $table->bigInteger('main_position_id');
            $table->enum('is_verified', [
                'BUDGET_VERIFIED_TYPE_NOT_VERIFIED',
                'BUDGET_VERIFIED_TYPE_CLOSED',
                'BUDGET_VERIFIED_TYPE_REQUESTED'
            ])->default('BUDGET_VERIFIED_TYPE_NOT_VERIFIED');
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
        Schema::dropIfExists('sub_positions');
    }
};
