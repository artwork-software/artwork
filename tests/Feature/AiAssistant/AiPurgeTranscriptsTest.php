<?php

namespace Tests\Feature\AiAssistant;

use Artwork\Modules\AiAssistant\Models\AiConversation;
use Artwork\Modules\AiAssistant\Models\AiConversationTurn;
use Artwork\Modules\AiAssistant\Settings\AiAssistantSettings;
use Artwork\Modules\User\Models\User;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/** core-chat-historie, Aufbewahrung (Konzept C3 §8). */
final class AiPurgeTranscriptsTest extends TestCase
{
    private function retention(int $days): void
    {
        AiAssistantSettings::fake([
            'enabled' => true,
            'level_b_enabled' => false,
            'level_c_enabled' => false,
            'transcript_retention_days' => $days,
            'ai_session_client_id' => null,
        ]);
    }

    private function conversationAged(int $daysAgo): AiConversation
    {
        $conversation = AiConversation::query()->create([
            'user_id' => User::factory()->create()->id,
            'title' => 'Gespraech',
            'status' => AiConversation::STATUS_ACTIVE,
            'last_turn_at' => now()->subDays($daysAgo),
        ]);

        AiConversationTurn::query()->create([
            'conversation_id' => $conversation->id,
            'client_turn_id' => (string) Str::uuid(),
            'user_message' => 'Frage',
            'status' => AiConversationTurn::STATUS_COMPLETED,
        ]);

        return $conversation;
    }

    #[Test]
    public function a_retention_of_zero_deletes_nothing(): void
    {
        $this->retention(0);
        $old = $this->conversationAged(999);

        $this->artisan('ai:purge-transcripts')->assertSuccessful();

        $this->assertDatabaseHas('ai_conversations', ['id' => $old->id]);
    }

    #[Test]
    public function it_deletes_only_conversations_older_than_the_retention(): void
    {
        $this->retention(30);
        $old = $this->conversationAged(31);
        $young = $this->conversationAged(29);

        $this->artisan('ai:purge-transcripts')->assertSuccessful();

        $this->assertDatabaseMissing('ai_conversations', ['id' => $old->id]);
        $this->assertDatabaseHas('ai_conversations', ['id' => $young->id]);
    }

    #[Test]
    public function deleting_cascades_to_the_turns(): void
    {
        $this->retention(30);
        $old = $this->conversationAged(90);

        $this->artisan('ai:purge-transcripts')->assertSuccessful();

        $this->assertDatabaseMissing('ai_conversation_turns', ['conversation_id' => $old->id]);
    }
}
