<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('artist_residencies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('civil_name')->nullable();
            $table->string('phone_number')->nullable();
            $table->string('position')->nullable();
            $table->foreignId('service_provider_id')->nullable()->constrained('service_providers');
            $table->foreignId('project_id')->nullable()->constrained('projects');
            $table->date('arrival_date')->nullable();
            $table->time('arrival_time')->nullable();
            $table->date('departure_date')->nullable();
            $table->time('departure_time')->nullable();
            $table->integer('days')->nullable();
            $table->string('type_of_room')->nullable();
            $table->float('cost_per_night')->nullable();
            $table->float('daily_allowance')->nullable();
            $table->float('additional_daily_allowance')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('artist_residencies');
    }
};
