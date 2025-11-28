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
        Schema::create('individual_time_series', function (Blueprint $table) {
            // Serie läuft komplett über UUID als Primary
            $table->uuid('uuid')->primary();

            // Optionaler Titel / Beschreibung der Serie
            $table->string('title')->nullable();

            // Zeitraum, in dem die Serie aktiv ist
            $table->date('start_date');
            $table->date('end_date');

            // Turnus: z.B. "weekly", "monthly" oder "custom"
            $table->string('frequency')->default('weekly');

            // Intervall im Turnus: z.B. 1 = jede Woche, 2 = alle 2 Wochen
            $table->unsignedInteger('interval')->default(1);

            // Ausgewählte Wochentage (z.B. [1,3,5] für Mo/Mi/Fr)
            // besser als JSON, damit du flexibel bleibst
            $table->json('weekdays');

            // Falls du später wissen willst, wer das angelegt hat
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('individual_time_series');
    }
};
