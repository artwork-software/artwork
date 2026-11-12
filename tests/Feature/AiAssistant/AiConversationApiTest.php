<?php

namespace Tests\Feature\AiAssistant;

use Artwork\Modules\AiAssistant\Models\AiConversation;
use Artwork\Modules\AiAssistant\Models\AiConversationTurn;
use Artwork\Modules\AiAssistant\Support\AiScopes;
use Artwork\Modules\User\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Passport\ClientRepository;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Concerns\GrantsAiAccess;

/**
 * core-chat-historie, API v1 fuer artwork-ai (Konzept C3 §5, §6, §9).
 */
final class AiConversationApiTest extends TestCase
{
    use GrantsAiAccess;

    protected function setUp(): void
    {
        parent::setUp();

        app(ClientRepository::class)
            ->createPersonalAccessGrantClient('PAT client', config('auth.guards.api.provider'));
    }

    /** @param string[] $scopes */
    private function actingAsToken(User $user, array $scopes = [AiScopes::TRANSCRIPT]): void
    {
        $token = $this->grantAiAccess($user)->createToken('ai-session-test', $scopes);
        $this->withHeader('Authorization', 'Bearer ' . $token->accessToken);
    }

    private function conversationFor(User $user): AiConversation
    {
        return AiConversation::query()->create([
            'user_id' => $user->id,
            'title' => 'Testgespraech',
            'status' => AiConversation::STATUS_ACTIVE,
        ]);
    }

    /** @return array<string, mixed> */
    private function turnPayload(array $overrides = []): array
    {
        return array_merge([
            'client_turn_id' => (string) Str::uuid(),
            'user_message' => 'Wie viele Schichten habe ich naechste Woche?',
            'assistant_message' => 'Vier.',
            'status' => AiConversationTurn::STATUS_COMPLETED,
            'tool_calls' => [['name' => 'shift_plan', 'label' => 'Dienstplan', 'status' => 'ok']],
            'context' => ['route' => 'shifts.index', 'locale' => 'de'],
        ], $overrides);
    }

    // --- Scope ---------------------------------------------------------------------------

    #[Test]
    public function a_token_without_the_transcript_scope_is_rejected(): void
    {
        $user = User::factory()->create();
        $this->actingAsToken($user, ['inventory:read']);

        $this->postJson('/api/v1/ai/conversations', ['title' => 'x'])->assertForbidden();
    }

    #[Test]
    public function without_a_token_there_is_no_access(): void
    {
        $this->postJson('/api/v1/ai/conversations', ['title' => 'x'])->assertUnauthorized();
    }

    // --- Anlegen und Lesen ---------------------------------------------------------------

    #[Test]
    public function it_creates_a_conversation(): void
    {
        $user = User::factory()->create();
        $this->actingAsToken($user);

        $response = $this->postJson('/api/v1/ai/conversations', ['title' => 'Dienstplanfragen'])
            ->assertCreated();

        $this->assertSame('Dienstplanfragen', $response->json('title'));
        $this->assertSame(AiConversation::STATUS_ACTIVE, $response->json('status'));
        $this->assertDatabaseHas('ai_conversations', ['id' => $response->json('id'), 'user_id' => $user->id]);
    }

    #[Test]
    public function a_title_is_required(): void
    {
        $this->actingAsToken(User::factory()->create());

        $this->postJson('/api/v1/ai/conversations', [])->assertStatus(422);
    }

    // --- Fremdzugriff --------------------------------------------------------------------

    /**
     * 404 statt 403: Ein 403 wuerde bestaetigen, dass es die ID gibt.
     *
     * @return array<string, array{0: string, 1: string}>
     */
    public static function apiEndpoints(): array
    {
        return [
            'show' => ['getJson', ''],
            'turns' => ['postJson', '/turns'],
        ];
    }

    #[Test]
    #[\PHPUnit\Framework\Attributes\DataProvider('apiEndpoints')]
    public function a_foreign_conversation_yields_404(string $method, string $suffix): void
    {
        $owner = User::factory()->create();
        $conversation = $this->conversationFor($owner);

        $this->actingAsToken(User::factory()->create());

        $payload = $suffix === '/turns' ? $this->turnPayload() : [];
        $this->{$method}('/api/v1/ai/conversations/' . $conversation->id . $suffix, $payload)
            ->assertNotFound();
    }

    /** Auch ein Admin darf fremde Gespraeche nicht sehen — Gate::before darf hier nicht greifen. */
    #[Test]
    #[\PHPUnit\Framework\Attributes\DataProvider('apiEndpoints')]
    public function an_admin_also_gets_404_for_a_foreign_conversation(string $method, string $suffix): void
    {
        $owner = User::factory()->create();
        $conversation = $this->conversationFor($owner);

        $this->actingAsToken($this->adminUser());

        $payload = $suffix === '/turns' ? $this->turnPayload() : [];
        $this->{$method}('/api/v1/ai/conversations/' . $conversation->id . $suffix, $payload)
            ->assertNotFound();
    }

    // --- Turns ---------------------------------------------------------------------------

    #[Test]
    public function it_records_a_turn_and_updates_the_conversation(): void
    {
        $user = User::factory()->create();
        $conversation = $this->conversationFor($user);
        $this->actingAsToken($user);

        $response = $this->postJson(
            '/api/v1/ai/conversations/' . $conversation->id . '/turns',
            $this->turnPayload()
        )->assertCreated();

        $this->assertSame('Vier.', $response->json('assistant_message'));
        $this->assertNotNull($conversation->fresh()->last_turn_at);
    }

    /** Der zweite POST traegt bewusst einen anderen Body — zurueck kommt trotzdem der erste Turn. */
    #[Test]
    public function repeating_a_turn_id_returns_the_existing_turn(): void
    {
        $user = User::factory()->create();
        $conversation = $this->conversationFor($user);
        $this->actingAsToken($user);

        $turnId = (string) Str::uuid();
        $url = '/api/v1/ai/conversations/' . $conversation->id . '/turns';

        $first = $this->postJson($url, $this->turnPayload([
            'client_turn_id' => $turnId,
            'user_message' => 'Erste Frage',
        ]))->assertCreated();

        $second = $this->postJson($url, $this->turnPayload([
            'client_turn_id' => $turnId,
            'user_message' => 'Voellig andere Frage',
            'assistant_message' => 'Andere Antwort',
        ]))->assertOk();

        $this->assertSame($first->json('id'), $second->json('id'));
        $this->assertSame('Erste Frage', $second->json('user_message'));
        $this->assertSame(1, AiConversationTurn::query()->where('conversation_id', $conversation->id)->count());
    }

    // --- Limits --------------------------------------------------------------------------

    /** @return array<string, array{0: array<string, mixed>, 1: string}> */
    public static function invalidTurns(): array
    {
        return [
            'user_message zu lang' => [['user_message' => 'x'], 'user_message'],
            'zu viele tool_calls' => [['tool_calls' => 'x'], 'tool_calls'],
            'pending_action gesetzt' => [['pending_action' => ['foo' => 'bar']], 'pending_action'],
        ];
    }

    #[Test]
    public function the_user_message_limit_is_enforced(): void
    {
        $user = User::factory()->create();
        $conversation = $this->conversationFor($user);
        $this->actingAsToken($user);

        $this->postJson('/api/v1/ai/conversations/' . $conversation->id . '/turns', $this->turnPayload([
            'user_message' => str_repeat('a', 4001),
        ]))->assertStatus(422)->assertJsonValidationErrors('user_message');
    }

    #[Test]
    public function the_assistant_message_byte_limit_is_enforced(): void
    {
        $user = User::factory()->create();
        $conversation = $this->conversationFor($user);
        $this->actingAsToken($user);

        $this->postJson('/api/v1/ai/conversations/' . $conversation->id . '/turns', $this->turnPayload([
            'assistant_message' => str_repeat('a', 65537),
        ]))->assertStatus(422)->assertJsonValidationErrors('assistant_message');
    }

    #[Test]
    public function too_many_tool_calls_are_rejected(): void
    {
        $user = User::factory()->create();
        $conversation = $this->conversationFor($user);
        $this->actingAsToken($user);

        $toolCalls = array_fill(0, 51, ['name' => 'n', 'label' => 'l', 'status' => 'ok']);

        $this->postJson('/api/v1/ai/conversations/' . $conversation->id . '/turns', $this->turnPayload([
            'tool_calls' => $toolCalls,
        ]))->assertStatus(422)->assertJsonValidationErrors('tool_calls');
    }

    #[Test]
    public function a_pending_action_is_rejected_until_w2(): void
    {
        $user = User::factory()->create();
        $conversation = $this->conversationFor($user);
        $this->actingAsToken($user);

        $this->postJson('/api/v1/ai/conversations/' . $conversation->id . '/turns', $this->turnPayload([
            'pending_action' => ['type' => 'create_event'],
        ]))->assertStatus(422)->assertJsonValidationErrors('pending_action');
    }

    // --- Lesen der Turns -----------------------------------------------------------------

    #[Test]
    public function it_returns_the_latest_turns_in_ascending_order_and_caps_the_limit(): void
    {
        $user = User::factory()->create();
        $conversation = $this->conversationFor($user);
        $this->actingAsToken($user);

        foreach (range(1, 60) as $i) {
            AiConversationTurn::query()->create([
                'conversation_id' => $conversation->id,
                'client_turn_id' => (string) Str::uuid(),
                'user_message' => 'Frage ' . $i,
                'status' => AiConversationTurn::STATUS_COMPLETED,
                'created_at' => now()->addSeconds($i),
                'updated_at' => now()->addSeconds($i),
            ]);
        }

        $response = $this->getJson('/api/v1/ai/conversations/' . $conversation->id . '?limit=999')
            ->assertOk();

        $turns = $response->json('turns');
        $this->assertCount(50, $turns, 'Der Limit-Parameter wird auf 50 gekappt');
        $this->assertSame('Frage 11', $turns[0]['user_message'], 'Die letzten 50, aufsteigend');
        $this->assertSame('Frage 60', $turns[49]['user_message']);
    }

    #[Test]
    public function the_default_limit_is_twenty(): void
    {
        $user = User::factory()->create();
        $conversation = $this->conversationFor($user);
        $this->actingAsToken($user);

        foreach (range(1, 25) as $i) {
            AiConversationTurn::query()->create([
                'conversation_id' => $conversation->id,
                'client_turn_id' => (string) Str::uuid(),
                'user_message' => 'Frage ' . $i,
                'status' => AiConversationTurn::STATUS_COMPLETED,
                'created_at' => now()->addSeconds($i),
                'updated_at' => now()->addSeconds($i),
            ]);
        }

        $this->getJson('/api/v1/ai/conversations/' . $conversation->id)
            ->assertOk()
            ->assertJsonCount(20, 'turns');
    }

    // --- Verschluesselung ----------------------------------------------------------------

    #[Test]
    public function the_messages_are_encrypted_at_rest(): void
    {
        $user = User::factory()->create();
        $conversation = $this->conversationFor($user);
        $this->actingAsToken($user);

        $this->postJson('/api/v1/ai/conversations/' . $conversation->id . '/turns', $this->turnPayload([
            'user_message' => 'Geheime Frage',
            'assistant_message' => 'Geheime Antwort',
        ]))->assertCreated();

        $raw = DB::table('ai_conversation_turns')
            ->where('conversation_id', $conversation->id)
            ->first(['user_message', 'assistant_message']);

        $this->assertStringNotContainsString('Geheime Frage', $raw->user_message);
        $this->assertStringNotContainsString('Geheime Antwort', $raw->assistant_message);

        $turn = AiConversationTurn::query()->where('conversation_id', $conversation->id)->first();
        $this->assertSame('Geheime Frage', $turn->user_message);
        $this->assertSame('Geheime Antwort', $turn->assistant_message);
    }
}
