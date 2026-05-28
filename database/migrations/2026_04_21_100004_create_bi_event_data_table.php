<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('bi_event_data', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->unsignedInteger('visitors')->nullable();
            $table->unsignedInteger('sold_tickets')->nullable();
            $table->decimal('revenue', 12, 2)->nullable();
            $table->timestamps();
            $table->unique(['project_id', 'event_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bi_event_data');
    }
};
