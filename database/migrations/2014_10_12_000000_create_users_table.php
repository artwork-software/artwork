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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('phone_number')->nullable();
            $table->string('password');
            $table->string('position');
            $table->string('business');
            $table->longText('description')->nullable();
            $table->boolean('toggle_hints')->default(true);
            $table->json('opened_checklists');
            $table->json('opened_areas');
            $table->rememberToken();
            $table->foreignId('current_team_id')->nullable();
            $table->string('profile_photo_path', 2048)->nullable();
            $table->boolean('temporary')->default(false);
            $table->date('employStart')->nullable();
            $table->date('employEnd')->nullable();
            $table->boolean('can_master')->default(false);
            $table->integer('weekly_working_hours')->default(40);
            $table->integer('salary_per_hour')->nullable();
            $table->longText('salary_description')->nullable();
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
        Schema::dropIfExists('users');
    }
};
