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
        Schema::create('freelancers', function (Blueprint $table) {
            $table->id();
            $table->string('position')->nullable();
            $table->string('profile_image')->nullable();
            $table->string('first_name')->default('Neuer');
            $table->string('last_name')->default('Freelancer');
            $table->string('work_name')->nullable();
            $table->string('work_description')->nullable();
            $table->string('email')->nullable();
            $table->string('phone_number')->nullable();
            $table->string('street')->nullable();
            $table->string('zip_code')->nullable();
            $table->string('location')->nullable();
            $table->string('note', 500)->nullable();
            $table->integer('salary_per_hour')->nullable();
            $table->longText('salary_description')->nullable();
            $table->boolean('can_master')->default(false);
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
        Schema::dropIfExists('freelancers');
    }
};
