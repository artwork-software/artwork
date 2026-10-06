<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Weitere Bilder der Produktion neben dem Hauptbild, in Reihenfolge des Hochladens.
        // remote_id ist die Kennung in tickets; null, solange das Bild dort noch nicht liegt.
        Schema::create('ticketing_production_images', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('ticketing_production_id')->constrained('ticketing_productions')->cascadeOnDelete();
            $table->string('path');
            $table->uuid('remote_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticketing_production_images');
    }
};
