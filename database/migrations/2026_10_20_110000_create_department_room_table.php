<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Raumzugriff für Teams: wie room_user, aber je Team (Abteilung). Alle Mitglieder eines Teams gelten als
     * Raumadmins (is_admin) bzw. dürfen Belegungen anfragen (can_request).
     */
    public function up(): void
    {
        if (Schema::hasTable('department_room')) {
            return;
        }

        Schema::create('department_room', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('department_id')->constrained()->cascadeOnDelete();
            $table->foreignId('room_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_admin')->default(false);
            $table->boolean('can_request')->default(false);
            $table->timestamps();

            $table->unique(['room_id', 'department_id']);
            $table->index(['department_id', 'is_admin']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('department_room');
    }
};
