<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ein Batch je ausgefuehrter KI-Aktion (core-undo; Konzept W3 §1).
 *
 * Die Aktion selbst ist ein Turn (W2); der Batch haelt fest, was dabei wirklich passiert ist —
 * damit „Rueckgaengig" nicht raten muss.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_action_batches', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            // Kein Fremdschluessel auf ai_conversations: die Historie wird nach Aufbewahrungsfrist
            // geloescht (C3 §8), der Batch soll das ueberleben.
            $table->uuid('conversation_id')->nullable();
            $table->string('tool');
            $table->string('status')->default('executed');
            $table->timestamp('executed_at');
            $table->timestamp('undo_until');
            $table->timestamp('reverted_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'executed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_action_batches');
    }
};
