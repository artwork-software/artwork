<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Chat-Historie des KI-Assistenten (core-chat-historie; Konzept C3 §2). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_conversations', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            // awaiting_confirmation wird erst mit W2 gesetzt; das Feld existiert schon.
            $table->string('status')->default('active');
            $table->timestamp('last_turn_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'last_turn_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_conversations');
    }
};
