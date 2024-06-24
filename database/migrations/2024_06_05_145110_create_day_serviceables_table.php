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
        Schema::create('day_serviceables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('day_service_id')->constrained()->onDelete('cascade');
            $table->date('date');
            $table->morphs('day_serviceable'); // Adds day_serviceable_id and day_serviceable_type
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('day_serviceables');
    }
};
