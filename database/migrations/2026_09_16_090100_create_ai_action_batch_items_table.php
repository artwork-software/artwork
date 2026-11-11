<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Die einzelnen Objekte eines Batches (core-undo; Konzept W3 §1).
 *
 * `before_state` traegt bei update und delete den Zustand vor der Aenderung — das ist alles, was
 * der Revert braucht.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_action_batch_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignUuid('batch_id')
                ->constrained('ai_action_batches')
                ->cascadeOnDelete();
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->string('action');
            $table->json('before_state')->nullable();
            $table->timestamps();

            $table->index(['model_type', 'model_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_action_batch_items');
    }
};
