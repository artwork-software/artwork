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
        Schema::create('craftables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('craft_id')->constrained()->onDelete('cascade');
            $table->morphs('craftable'); // Fügt `craftable_id` und `craftable_type` hinzu
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('craftables');
    }
};
