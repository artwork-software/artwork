<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Registry für einmalig auszuführende Update-/Reparatur-Tasks, die nicht als Migration laufen,
 * sondern aus `artwork:update` heraus aufgerufen werden (z.B. Daten-Backfills). Erlaubt es,
 * solche Tasks idempotent über Deployments hinweg genau einmal pro Umgebung laufen zu lassen.
 */
return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('one_time_tasks')) {
            return;
        }

        Schema::create('one_time_tasks', function (Blueprint $table): void {
            $table->id();
            $table->string('key')->unique();
            $table->timestamp('executed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('one_time_tasks');
    }
};
