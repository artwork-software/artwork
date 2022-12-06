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
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->string("name");
            $table->string('basename')->unique();
            $table->string("contract_partner");
            $table->integer('amount');
            $table->unsignedBigInteger('project_id');
            $table->string('description');
            $table->string('legal_form');
            $table->boolean('ksk_liable')->default(false);
            $table->boolean('resident_abroad')->default(false);
            $table->string('type');
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
        Schema::dropIfExists('contracts');
    }
};
