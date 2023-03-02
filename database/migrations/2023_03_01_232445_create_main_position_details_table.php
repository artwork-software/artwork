<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('main_position_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger("main_position_id");
            $table->unsignedBigInteger("column_id");
            $table->timestamps();
        });
    }
};
