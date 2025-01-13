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
        Schema::table('shifts', function (Blueprint $table) {
            // Ändere die Spalte `event_id` auf nullable
            $table->unsignedBigInteger('event_id')->nullable()->change();

            // Fremdschlüssel aktualisieren
            $table->dropForeign(['event_id']); // Bestehenden Fremdschlüssel entfernen
            $table->foreign('event_id') // Neuen Fremdschlüssel hinzufügen
            ->references('id')
                ->on('events')
                ->onDelete('set null');

            // Füge die neue Spalte `room_id` hinzu
            $table->foreignId('room_id')->nullable()->constrained()->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            $table->dropForeign(['event_id']);

            // Ändere `event_id` zurück zu nicht nullable, falls erforderlich
            $table->unsignedBigInteger('event_id')->nullable(false)->change();

            // Fremdschlüssel für `room_id` entfernen und Spalte löschen
            $table->dropForeign(['room_id']);
            $table->dropColumn('room_id');
        });
    }
};
