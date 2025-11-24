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
        Schema::create('committed_shift_changes', function (Blueprint $table) {
            $table->id();

            // Gewerk (für Filter / Kontext) – historienfreundlich
            $table->foreignId('craft_id')
                ->nullable()
                ->constrained('crafts')
                ->nullOnDelete();

            // Zugehörige Schicht (kann später gelöscht werden, daher nullable + nullOnDelete)
            $table->foreignId('shift_id')
                ->nullable()
                ->constrained('shifts')
                ->nullOnDelete();

            // Polymorphe Relation zum geänderten Objekt (z.B. Shift, ShiftAssignment)
            $table->string('subject_type');
            $table->unsignedBigInteger('subject_id');

            // Art der Änderung (created, updated, deleted, ...)
            $table->string('change_type', 50);

            // Feldänderungen als JSON-Diff
            $table->json('field_changes');

            // Betroffener Benutzer (kann User, Freelancer oder ServiceProvider sein) — polymorph
            $table->string('affected_user_type')->nullable();
            $table->unsignedBigInteger('affected_user_id')->nullable();

            // Ausführender Benutzer (wer hat die Änderung vorgenommen)
            $table->foreignId('changed_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // Zeitpunkt der Änderung
            $table->timestamp('changed_at')->useCurrent();

            // Nachträgliche Zustimmung
            $table->timestamp('acknowledged_at')->nullable();

            $table->foreignId('acknowledged_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            // Sinnvolle Indexe für Abfragen
            $table->index(['craft_id', 'shift_id']);
            $table->index(['subject_type', 'subject_id']);
            $table->index(['changed_at']);
            $table->index(['acknowledged_at']);

            // Index für polymorphe affected_user
            // Explicit shorter index name to avoid MySQL identifier length limit
            $table->index(['affected_user_type', 'affected_user_id'], 'committed_shift_changes_aff_user_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('committed_shift_changes');
    }
};
