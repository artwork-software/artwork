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
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->longText('description')->nullable();
            $table->string('number_of_participants')->nullable();
            $table->string('num_of_guests')->nullable()->default(null);
            $table->string('entry_fee')->nullable()->default(null);
            $table->boolean('registration_required')->nullable()->default(false);
            $table->string('register_by')->nullable()->default(null);
            $table->string('registration_deadline')->nullable()->default(null);
            $table->boolean('closed_society')->nullable()->default(false);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('projects');
    }
};
