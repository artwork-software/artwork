<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bestätigte Belegung eines Termins (Raum + Zeitraum), soweit sie vom aktuellen Stand abweicht, und die Person,
 * die die laufende Raumanfrage gestellt hat. Leer heißt: der aktuelle Stand ist die bestätigte Belegung bzw. die
 * Anfrage stammt von der Person, die den Termin angelegt hat. Deshalb braucht es keine Datenübernahme.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table): void {
            $table->foreignId('approved_room_id')->nullable()->after('declined_room_id')
                ->constrained('rooms')->nullOnDelete();
            $table->dateTime('approved_start_time')->nullable()->after('approved_room_id');
            $table->dateTime('approved_end_time')->nullable()->after('approved_start_time');
            $table->foreignId('requested_by_id')->nullable()->after('approved_end_time')
                ->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('requested_by_id');
            $table->dropColumn(['approved_start_time', 'approved_end_time']);
            $table->dropConstrainedForeignId('approved_room_id');
        });
    }
};
