<?php

namespace Tests\Feature\AiAssistant;

use Artwork\Modules\AiAssistant\Models\AiConversation;
use Artwork\Modules\AiAssistant\Models\AiConversationTurn;
use Artwork\Modules\AiAssistant\Settings\AiAssistantSettings;
use Artwork\Modules\User\Models\User;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

/** core-chat-historie, Web-Endpunkte fuer die Bar (Konzept C3 §5, §7). */
final class AiConversationWebTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->enableAssistant();
    }

    private function enableAssistant(bool $enabled = true): void
    {
        config(['ai.enabled' => true]);

        AiAssistantSettings::fake([
            'enabled' => $enabled,
            'level_b_enabled' => false,
            'level_c_enabled' => false,
            'transcript_retention_days' => 0,
            'ai_session_client_id' => null,
        ]);
    }

    private function conversationFor(User $user, string $title = 'Gespraech'): AiConversation
    {
        return AiConversation::query()->create([
            'user_id' => $user->id,
            'title' => $title,
            'status' => AiConversation::STATUS_ACTIVE,
            'last_turn_at' => now(),
        ]);
    }

    #[Test]
    public function it_lists_only_the_own_conversations_newest_first(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $older = $this->conversationFor($user, 'Aelter');
        $older->forceFill(['last_turn_at' => now()->subDay()])->save();
        $newer = $this->conversationFor($user, 'Neuer');
        $this->conversationFor(User::factory()->create(), 'Fremd');

        $response = $this->getJson(route('ai.conversations.index'))->assertOk();

        $titles = array_column($response->json('data'), 'title');
        $this->assertSame(['Neuer', 'Aelter'], $titles);
        $this->assertSame(2, $response->json('total'));
        $this->assertNotNull($newer->id);
    }

    #[Test]
    public function a_disabled_assistant_blocks_the_endpoints(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $conversation = $this->conversationFor($user);
        $this->enableAssistant(false);

        $this->getJson(route('ai.conversations.index'))->assertForbidden();
        $this->deleteJson(route('ai.conversations.destroy', $conversation->id))->assertForbidden();
    }

    #[Test]
    public function a_disabled_assistant_blocks_an_admin_too(): void
    {
        $this->actingAs($this->adminUser());
        $this->enableAssistant(false);

        $this->getJson(route('ai.conversations.index'))->assertForbidden();
    }

    #[Test]
    public function it_deletes_an_own_conversation_with_its_turns(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $conversation = $this->conversationFor($user, 'Zu loeschen');

        AiConversationTurn::query()->create([
            'conversation_id' => $conversation->id,
            'client_turn_id' => (string) Str::uuid(),
            'user_message' => 'Frage',
            'status' => AiConversationTurn::STATUS_COMPLETED,
        ]);

        $this->deleteJson(route('ai.conversations.destroy', $conversation->id))->assertNoContent();

        $this->assertDatabaseMissing('ai_conversations', ['id' => $conversation->id]);
        $this->assertDatabaseMissing('ai_conversation_turns', ['conversation_id' => $conversation->id]);
    }

    #[Test]
    public function deleting_writes_an_activity_entry_without_message_content(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $conversation = $this->conversationFor($user, 'Mein Titel');

        AiConversationTurn::query()->create([
            'conversation_id' => $conversation->id,
            'client_turn_id' => (string) Str::uuid(),
            'user_message' => 'Streng geheime Frage',
            'status' => AiConversationTurn::STATUS_COMPLETED,
        ]);

        $this->deleteJson(route('ai.conversations.destroy', $conversation->id))->assertNoContent();

        $activity = Activity::query()->where('description', 'ai_conversation_deleted')->latest('id')->first();

        $this->assertNotNull($activity);
        $this->assertSame('Mein Titel', $activity->properties['title']);
        $this->assertStringNotContainsString('Streng geheime Frage', $activity->properties->toJson());
    }

    #[Test]
    public function a_foreign_conversation_can_not_be_deleted(): void
    {
        $owner = User::factory()->create();
        $conversation = $this->conversationFor($owner);

        $this->actingAs(User::factory()->create());

        $this->deleteJson(route('ai.conversations.destroy', $conversation->id))->assertNotFound();
        $this->assertDatabaseHas('ai_conversations', ['id' => $conversation->id]);
    }

    /** Gate::before darf hier nicht greifen — ein Gespraech gehoert nur der fuehrenden Person. */
    #[Test]
    public function an_admin_can_not_delete_a_foreign_conversation(): void
    {
        $owner = User::factory()->create();
        $conversation = $this->conversationFor($owner);

        $this->actingAs($this->adminUser());

        $this->deleteJson(route('ai.conversations.destroy', $conversation->id))->assertNotFound();
        $this->assertDatabaseHas('ai_conversations', ['id' => $conversation->id]);
    }

    #[Test]
    public function an_admin_does_not_see_foreign_conversations_in_the_list(): void
    {
        $this->conversationFor(User::factory()->create(), 'Fremd');

        $this->actingAs($this->adminUser());

        $this->getJson(route('ai.conversations.index'))->assertOk()->assertJsonCount(0, 'data');
    }
}
