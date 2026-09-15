<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Welcher artwork-Raum welcher Spielstätte in tickets entspricht — Grundlage für jede spätere
        // Synchronisation (Termine im Raum → Veranstaltungen in der Spielstätte).
        Schema::create('ticketing_room_links', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('room_id')->unique()->constrained('rooms')->cascadeOnDelete();
            $table->uuid('venue_id');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticketing_room_links');
    }
};
