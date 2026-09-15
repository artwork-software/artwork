<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Welche Preisklassen ein Termin im Ticketshop verkauft, mit Plätzen und Preis, und ob er dort
        // schon verkauft wird. Ohne Zeile gelten die Preisklassen der Spielstätte; erst die Freigabe
        // legt den Termin in artwork tickets an und merkt sich dessen ID.
        Schema::create('ticketing_event_releases', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('event_id')->unique()->constrained('events')->cascadeOnDelete();
            $table->json('classes');
            $table->string('state', 20)->default('draft');
            $table->uuid('tickets_date_id')->nullable();
            $table->timestamp('released_at')->nullable();
            $table->foreignId('released_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticketing_event_releases');
    }
};
