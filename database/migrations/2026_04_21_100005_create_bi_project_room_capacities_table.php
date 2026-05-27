<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('bi_project_room_capacities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('room_id')->constrained('rooms')->cascadeOnDelete();
            $table->unsignedInteger('capacity_override')->nullable();
            $table->timestamps();
            $table->unique(['project_id', 'room_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bi_project_room_capacities');
    }
};
