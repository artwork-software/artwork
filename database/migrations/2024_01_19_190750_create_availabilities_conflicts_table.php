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
    public function up(): void
    {
        Schema::create('availabilities_conflicts', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('availability_id');
            $table->unsignedBigInteger('shift_id');
            $table->string('user_name');
            $table->date('date');
            $table->time('start_time');
            $table->time('end_time');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('availabilities_conflicts');
    }
};
