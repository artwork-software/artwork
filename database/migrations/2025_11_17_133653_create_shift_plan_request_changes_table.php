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
        Schema::create('shift_plan_request_changes', function (Blueprint $table) {
            $table->id();

            // Zugehörige Dienstplananfrage
            $table->foreignId('shift_plan_request_id')
                ->constrained('shift_plan_requests')
                ->cascadeOnDelete();

            // Polymorphes Zielobjekt (z.B. Shift oder ShiftAssignment)
            $table->string('subject_type');
            $table->unsignedBigInteger('subject_id');

            // Art der Änderung (created, updated, deleted, ...)
            $table->string('change_type', 50);

            // Feldänderungen als JSON-Diff
            $table->json('field_changes');

            // Betroffener Benutzer (z.B. Mitarbeiter in der Schicht)
            $table->foreignId('affected_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // Ausführender Benutzer (wer hat die Änderung vorgenommen)
            $table->foreignId('changed_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // Zeitpunkt der Änderung
            $table->timestamp('changed_at')->useCurrent();

            $table->timestamps();

            // Sinnvolle Indexe
            $table->index(['shift_plan_request_id']);
            $table->index(['subject_type', 'subject_id']);
            $table->index(['changed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shift_plan_request_changes');
    }
};
