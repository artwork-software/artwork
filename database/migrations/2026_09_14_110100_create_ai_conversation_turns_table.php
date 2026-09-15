<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ein Insert je abgeschlossenem Turn (core-chat-historie; Konzept C3 §2).
 *
 * user_message und assistant_message tragen einen encrypted-Cast. Das Chiffrat ist rund 1,4× so
 * lang wie der Klartext und nicht durchsuchbar — deshalb text, nicht string.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_conversation_turns', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('conversation_id')
                ->constrained('ai_conversations')
                ->cascadeOnDelete();
            $table->uuid('client_turn_id');
            $table->text('user_message');
            $table->text('assistant_message')->nullable();
            $table->string('status');
            $table->string('error_code')->nullable();
            $table->json('tool_calls')->nullable();
            $table->json('context')->nullable();
            // Erst mit W2 befuellt.
            $table->json('pending_action')->nullable();
            $table->timestamps();

            // Traegt die Idempotenz: ein wiederholter POST mit bekannter client_turn_id liefert
            // den bestehenden Turn statt eines Duplikats.
            $table->unique(['conversation_id', 'client_turn_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_conversation_turns');
    }
};
