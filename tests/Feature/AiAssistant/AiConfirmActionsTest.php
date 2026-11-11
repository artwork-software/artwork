<?php

namespace Tests\Feature\AiAssistant;

use Artwork\Modules\AiAssistant\Models\AiConversation;
use Artwork\Modules\AiAssistant\Models\AiConversationTurn;
use Artwork\Modules\AiAssistant\Services\AiActionService;
use Artwork\Modules\AiAssistant\Settings\AiAssistantSettings;
use Artwork\Modules\AiAssistant\Support\AiActionHash;
use Artwork\Modules\AiAssistant\Support\AiScopes;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\EventType\Models\EventType;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\User\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Passport;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * core-confirm-actions: Aktion, Write-Token, Pruefkette, Revocation, Cancel, Ablauf
 * (Konzept W2 §1–§4, §6, §8).
 */
final class AiConfirmActionsTest extends TestCase
{
    private const MONDAY = '2026-09-14';

    private Room $room;

    private EventType $eventType;

    private Project $project;

    private \Laravel\Passport\Client $aiClient;

    protected function setUp(): void
    {
        parent::setUp();

        app(ClientRepository::class)
            ->createPersonalAccessGrantClient('PAT client', config('auth.guards.api.provider'));

        // Der ai_session-Grant braucht den Client der Instanz (core-session-token).
        $this->aiClient = Passport::client()->newQuery()->forceCreate([
            'name' => 'ai-session',
            'secret' => null,
            'provider' => config('auth.guards.api.provider'),
            'redirect_uris' => [],
            'grant_types' => ['ai_session'],
            'revoked' => false,
        ]);

        $this->levelC(true);

        $this->room = Room::factory()->create(['name' => 'Studio 1']);
        $this->eventType = EventType::factory()->create();
        $this->project = Project::factory()->create(['name' => 'Tosca']);
    }

    private function levelC(bool $enabled): void
    {
        config(['ai.enabled' => true]);

        AiAssistantSettings::fake([
            'enabled' => true,
            'level_b_enabled' => true,
            'level_c_enabled' => $enabled,
            'transcript_retention_days' => 0,
            'ai_session_client_id' => (string) $this->aiClient->getKey(),
        ]);
    }

    /** @param string[] $scopes */
    private function actingAsToken(User $user, array $scopes = [AiScopes::WRITE]): string
    {
        $this->app['auth']->forgetGuards();

        $token = $user->createToken('ai-session-test', $scopes);
        $this->withHeader('Authorization', 'Bearer ' . $token->accessToken);

        return (string) $token->token->id;
    }

    private function planner(): User
    {
        $user = User::factory()->create();

        foreach ([PermissionEnum::PROJECT_VIEW->value, PermissionEnum::CREATE_EVENTS_WITHOUT_REQUEST->value] as $name) {
            $user->givePermissionTo(Permission::findOrCreate($name, 'web'));
        }

        return $user->fresh();
    }

    /**
     * @return array<string, mixed>
     */
    private function createArgs(): array
    {
        return [
            'project_id' => $this->project->id,
            'series_spec' => [
                'from' => self::MONDAY,
                'to' => '2026-09-28',
                'weekdays' => [1],
                'start_time' => '19:00',
                'duration_minutes' => 120,
                'room_id' => $this->room->id,
                'type_id' => $this->eventType->id,
                'name' => 'Vorstellung',
            ],
        ];
    }

    /**
     * Eine offene Aktion, wie artwork-ai sie schreiben wuerde.
     *
     * @param array<string, mixed>|null $args
     */
    private function pendingAction(
        User $user,
        ?array $args = null,
        ?string $expiresAt = null,
        string $tool = 'create_events'
    ): AiConversationTurn {
        $args ??= $this->createArgs();

        $conversation = AiConversation::query()->create([
            'user_id' => $user->id,
            'title' => 'Serie anlegen',
            'status' => AiConversation::STATUS_AWAITING_CONFIRMATION,
        ]);

        return $conversation->turns()->create([
            'client_turn_id' => (string) Str::uuid(),
            'user_message' => 'Lege die Vorstellungen an',
            'assistant_message' => null,
            'status' => AiConversationTurn::STATUS_AWAITING_CONFIRMATION,
            'tool_calls' => [],
            'context' => [],
            'pending_action' => [
                'tool' => $tool,
                'args' => $args,
                'args_hash' => AiActionHash::of($args),
                'preview' => ['summary' => '3 Termine anlegen', 'items' => []],
                'expires_at' => $expiresAt ?? CarbonImmutable::now()->addMinutes(5)->toIso8601String(),
            ],
        ]);
    }

    /**
     * @return array{0: string, 1: AiConversationTurn} Bearer-Token und Aktion
     */
    private function writeTokenFor(User $user, AiConversationTurn $turn): array
    {
        $this->app['auth']->forgetGuards();

        $response = $this->actingAs($user)->postJson('/ai/session', [
            'scopes' => [AiScopes::WRITE],
            'action_id' => $turn->id,
        ])->assertOk();

        $this->app['auth']->forgetGuards();
        $this->withHeader('Authorization', 'Bearer ' . $response->json('access_token'));

        return [$response->json('access_token'), $turn->fresh()];
    }

    // --- Turn-Endpunkt ------------------------------------------------------------------------

    #[Test]
    public function a_turn_may_carry_a_pending_action(): void
    {
        $user = $this->planner();
        $conversation = AiConversation::query()->create([
            'user_id' => $user->id,
            'title' => 'Serie',
            'status' => AiConversation::STATUS_ACTIVE,
        ]);
        $args = $this->createArgs();
        $this->actingAsToken($user, [AiScopes::TRANSCRIPT]);

        $this->postJson('/api/v1/ai/conversations/' . $conversation->id . '/turns', [
            'client_turn_id' => (string) Str::uuid(),
            'user_message' => 'Lege die Vorstellungen an',
            'status' => AiConversationTurn::STATUS_AWAITING_CONFIRMATION,
            'pending_action' => [
                'tool' => 'create_events',
                'args' => $args,
                'args_hash' => AiActionHash::of($args),
                'preview' => ['summary' => '3 Termine anlegen', 'items' => []],
                'expires_at' => CarbonImmutable::now()->addMinutes(5)->toIso8601String(),
            ],
        ])->assertCreated();

        $this->assertSame(AiConversation::STATUS_AWAITING_CONFIRMATION, $conversation->fresh()->status);
    }

    #[Test]
    public function a_pending_action_with_a_wrong_hash_is_rejected(): void
    {
        $user = $this->planner();
        $conversation = AiConversation::query()->create([
            'user_id' => $user->id,
            'title' => 'Serie',
            'status' => AiConversation::STATUS_ACTIVE,
        ]);
        $this->actingAsToken($user, [AiScopes::TRANSCRIPT]);

        $this->postJson('/api/v1/ai/conversations/' . $conversation->id . '/turns', [
            'client_turn_id' => (string) Str::uuid(),
            'user_message' => 'Lege die Vorstellungen an',
            'status' => AiConversationTurn::STATUS_AWAITING_CONFIRMATION,
            'pending_action' => [
                'tool' => 'create_events',
                'args' => $this->createArgs(),
                'args_hash' => str_repeat('a', 64),
                'preview' => ['summary' => '3 Termine anlegen', 'items' => []],
                'expires_at' => CarbonImmutable::now()->addMinutes(5)->toIso8601String(),
            ],
        ])->assertStatus(422)->assertJsonValidationErrors('pending_action.args_hash');
    }

    #[Test]
    public function a_pending_action_beyond_the_maximum_window_is_rejected(): void
    {
        $user = $this->planner();
        $conversation = AiConversation::query()->create([
            'user_id' => $user->id,
            'title' => 'Serie',
            'status' => AiConversation::STATUS_ACTIVE,
        ]);
        $args = $this->createArgs();
        $this->actingAsToken($user, [AiScopes::TRANSCRIPT]);

        $this->postJson('/api/v1/ai/conversations/' . $conversation->id . '/turns', [
            'client_turn_id' => (string) Str::uuid(),
            'user_message' => 'Lege die Vorstellungen an',
            'status' => AiConversationTurn::STATUS_AWAITING_CONFIRMATION,
            'pending_action' => [
                'tool' => 'create_events',
                'args' => $args,
                'args_hash' => AiActionHash::of($args),
                'preview' => ['summary' => '3 Termine anlegen', 'items' => []],
                'expires_at' => CarbonImmutable::now()->addHours(2)->toIso8601String(),
            ],
        ])->assertStatus(422)->assertJsonValidationErrors('pending_action.expires_at');
    }

    // --- Token-Ausstellung --------------------------------------------------------------------

    #[Test]
    public function a_write_token_needs_an_action_id(): void
    {
        $user = $this->planner();

        $this->actingAs($user)
            ->postJson('/ai/session', ['scopes' => [AiScopes::WRITE]])
            ->assertStatus(422)
            ->assertJsonValidationErrors('action_id');
    }

    #[Test]
    public function an_unknown_or_foreign_or_expired_action_is_refused(): void
    {
        $user = $this->planner();

        $this->actingAs($user)
            ->postJson('/ai/session', ['scopes' => [AiScopes::WRITE], 'action_id' => (string) Str::uuid()])
            ->assertStatus(422);

        $foreign = $this->pendingAction(User::factory()->create());
        $this->app['auth']->forgetGuards();
        $this->actingAs($user)
            ->postJson('/ai/session', ['scopes' => [AiScopes::WRITE], 'action_id' => $foreign->id])
            ->assertForbidden();

        $expired = $this->pendingAction($user, null, CarbonImmutable::now()->subMinute()->toIso8601String());
        $this->app['auth']->forgetGuards();
        $this->actingAs($user)
            ->postJson('/ai/session', ['scopes' => [AiScopes::WRITE], 'action_id' => $expired->id])
            ->assertStatus(422);
    }

    #[Test]
    public function a_house_without_level_c_gets_no_write_token(): void
    {
        $this->levelC(false);
        $user = $this->planner();
        $turn = $this->pendingAction($user);

        $this->actingAs($user)
            ->postJson('/ai/session', ['scopes' => [AiScopes::WRITE], 'action_id' => $turn->id])
            ->assertForbidden();
    }

    #[Test]
    public function a_write_token_carries_only_write_and_transcript_and_binds_its_jti(): void
    {
        $user = $this->planner();
        $turn = $this->pendingAction($user);

        $response = $this->actingAs($user)->postJson('/ai/session', [
            'scopes' => [AiScopes::WRITE],
            'action_id' => $turn->id,
        ])->assertOk();

        $this->assertSame([AiScopes::WRITE, AiScopes::TRANSCRIPT], $response->json('scopes'));
        $this->assertNotNull($turn->fresh()->pending_action['jti'] ?? null);
    }

    // --- Ausfuehrung --------------------------------------------------------------------------

    #[Test]
    public function a_confirmed_action_executes_once_and_burns_its_token(): void
    {
        $user = $this->planner();
        $args = $this->createArgs();
        $turn = $this->pendingAction($user, $args);
        [, $turn] = $this->writeTokenFor($user, $turn);

        $payload = $this->postJson(
            '/api/v1/ai/events/batch',
            array_merge($args, ['dry_run' => false, 'action_id' => $turn->id])
        )->assertOk()->json();

        $this->assertCount(3, $payload['ids']);
        $this->assertSame(3, Event::query()->where('project_id', $this->project->id)->count());

        $turn = $turn->fresh();
        $this->assertSame(AiActionService::STATUS_EXECUTED, $turn->status);
        $this->assertNotNull($turn->pending_action['executed_at']);
        $this->assertSame(AiConversation::STATUS_ACTIVE, $turn->conversation->status);

        // Single-Use: das Token ist revoziert, der naechste Aufruf kommt nicht mehr durch die Auth.
        $this->assertTrue((bool) Passport::token()->newQuery()->find($turn->pending_action['jti'])->revoked);

        // Ohne forgetGuards() haelt der RequestGuard den aufgeloesten Benutzer des ersten Aufrufs
        // fest und das Token wuerde gar nicht erneut geprueft.
        $this->app['auth']->forgetGuards();

        $this->postJson(
            '/api/v1/ai/events/batch',
            array_merge($args, ['dry_run' => false, 'action_id' => $turn->id])
        )->assertUnauthorized();
    }

    #[Test]
    public function a_second_execution_with_a_fresh_token_is_a_conflict(): void
    {
        $user = $this->planner();
        $args = $this->createArgs();
        $turn = $this->pendingAction($user, $args);
        [, $turn] = $this->writeTokenFor($user, $turn);

        $this->postJson(
            '/api/v1/ai/events/batch',
            array_merge($args, ['dry_run' => false, 'action_id' => $turn->id])
        )->assertOk();

        // Frisches Token mit denselben Scopes — die Aktion selbst ist verbraucht.
        $this->actingAsToken($user, [AiScopes::WRITE]);

        $this->postJson(
            '/api/v1/ai/events/batch',
            array_merge($args, ['dry_run' => false, 'action_id' => $turn->id])
        )->assertStatus(409);

        $this->assertSame(3, Event::query()->where('project_id', $this->project->id)->count());
    }

    #[Test]
    public function changed_arguments_are_a_conflict(): void
    {
        $user = $this->planner();
        $args = $this->createArgs();
        $turn = $this->pendingAction($user, $args);
        [, $turn] = $this->writeTokenFor($user, $turn);

        $changed = $args;
        $changed['series_spec']['start_time'] = '20:00';

        $this->postJson(
            '/api/v1/ai/events/batch',
            array_merge($changed, ['dry_run' => false, 'action_id' => $turn->id])
        )->assertStatus(409);

        $this->assertSame(0, Event::query()->where('project_id', $this->project->id)->count());
    }

    #[Test]
    public function a_write_token_cannot_execute_a_foreign_action(): void
    {
        $user = $this->planner();
        $args = $this->createArgs();
        $own = $this->pendingAction($user, $args);
        $foreign = $this->pendingAction(User::factory()->create(), $args);
        [, $own] = $this->writeTokenFor($user, $own);

        $this->postJson(
            '/api/v1/ai/events/batch',
            array_merge($args, ['dry_run' => false, 'action_id' => $foreign->id])
        )->assertForbidden();

        $this->assertSame(0, Event::query()->where('project_id', $this->project->id)->count());
    }

    #[Test]
    public function a_token_of_another_action_does_not_fit(): void
    {
        $user = $this->planner();
        $args = $this->createArgs();
        $first = $this->pendingAction($user, $args);
        $second = $this->pendingAction($user, $args);

        // Token gehoert zur ersten Aktion, ausgefuehrt werden soll die zweite.
        [, $first] = $this->writeTokenFor($user, $first);

        $this->postJson(
            '/api/v1/ai/events/batch',
            array_merge($args, ['dry_run' => false, 'action_id' => $second->id])
        )->assertUnauthorized();
    }

    // --- Aktion lesen, abbrechen, ablaufen -----------------------------------------------------

    #[Test]
    public function the_action_endpoint_hides_the_jti(): void
    {
        $user = $this->planner();
        $turn = $this->pendingAction($user);
        [, $turn] = $this->writeTokenFor($user, $turn);

        $payload = $this->getJson('/api/v1/ai/actions/' . $turn->id)->assertOk()->json();

        $this->assertSame($turn->id, $payload['id']);
        $this->assertSame('create_events', $payload['tool']);
        $this->assertSame($this->project->id, $payload['args']['project_id']);
        $this->assertArrayNotHasKey('jti', $payload);
    }

    #[Test]
    public function the_action_endpoint_refuses_a_foreign_action(): void
    {
        $user = $this->planner();
        $own = $this->pendingAction($user);
        $foreign = $this->pendingAction(User::factory()->create());
        $this->writeTokenFor($user, $own);

        $this->getJson('/api/v1/ai/actions/' . $foreign->id)->assertForbidden();
    }

    #[Test]
    public function cancelling_leaves_everything_else_untouched(): void
    {
        $user = $this->planner();
        $turn = $this->pendingAction($user);

        $this->actingAs($user)
            ->postJson('/ai/actions/' . $turn->id . '/cancel')
            ->assertOk()
            ->assertJsonPath('status', AiConversationTurn::STATUS_CANCELLED);

        $turn = $turn->fresh();
        $this->assertSame(AiConversationTurn::STATUS_CANCELLED, $turn->status);
        $this->assertSame(AiConversation::STATUS_ACTIVE, $turn->conversation->status);
        $this->assertSame(0, Event::query()->where('project_id', $this->project->id)->count());
    }

    #[Test]
    public function cancelling_a_foreign_action_is_forbidden(): void
    {
        $foreign = $this->pendingAction(User::factory()->create());

        $this->actingAs($this->planner())
            ->postJson('/ai/actions/' . $foreign->id . '/cancel')
            ->assertForbidden();
    }

    #[Test]
    public function the_expiry_job_releases_stale_actions(): void
    {
        $user = $this->planner();
        $stale = $this->pendingAction($user, null, CarbonImmutable::now()->subMinutes(20)->toIso8601String());
        $fresh = $this->pendingAction($user);

        $this->artisan('ai:expire-actions')->assertSuccessful();

        $this->assertSame(AiActionService::STATUS_EXPIRED, $stale->fresh()->status);
        $this->assertSame(AiConversation::STATUS_ACTIVE, $stale->fresh()->conversation->status);
        $this->assertSame(AiConversationTurn::STATUS_AWAITING_CONFIRMATION, $fresh->fresh()->status);
    }

    #[Test]
    public function a_pending_action_needs_a_contract_shaped_preview(): void
    {
        $user = $this->planner();
        $conversation = AiConversation::query()->create([
            'user_id' => $user->id,
            'title' => 'Serie',
            'status' => AiConversation::STATUS_ACTIVE,
        ]);
        $args = $this->createArgs();
        $this->actingAsToken($user, [AiScopes::TRANSCRIPT]);

        // summary ist im Contract ein Text, items eine Liste mit action/label
        // (sse-events/confirm.request.schema.json).
        $this->postJson('/api/v1/ai/conversations/' . $conversation->id . '/turns', [
            'client_turn_id' => (string) Str::uuid(),
            'user_message' => 'Lege die Vorstellungen an',
            'status' => AiConversationTurn::STATUS_AWAITING_CONFIRMATION,
            'pending_action' => [
                'tool' => 'create_events',
                'args' => $args,
                'args_hash' => AiActionHash::of($args),
                'preview' => ['summary' => ['total' => 3], 'items' => [['label' => 'ohne action']]],
                'expires_at' => CarbonImmutable::now()->addMinutes(5)->toIso8601String(),
            ],
        ])->assertStatus(422)->assertJsonValidationErrors([
            'pending_action.preview.summary',
            'pending_action.preview.items.0.action',
        ]);
    }

    /** contracts/action-contract.md: falsches Werkzeug ist 422, nicht 409. */
    #[Test]
    public function an_action_for_another_tool_is_rejected(): void
    {
        $user = $this->planner();
        $args = $this->createArgs();
        $turn = $this->pendingAction($user, $args, null, 'delete_events');
        [, $turn] = $this->writeTokenFor($user, $turn);

        $this->postJson(
            '/api/v1/ai/events/batch',
            array_merge($args, ['dry_run' => false, 'action_id' => $turn->id])
        )->assertStatus(422);

        $this->assertSame(0, Event::query()->where('project_id', $this->project->id)->count());
    }

    /** Die Bar liest Batch und Undo-Frist ueber den Web-Endpunkt (core-ki-bar-confirm). */
    #[Test]
    public function the_bar_endpoint_reports_the_batch_of_an_executed_action(): void
    {
        $user = $this->planner();
        $args = $this->createArgs();
        $turn = $this->pendingAction($user, $args);
        [, $turn] = $this->writeTokenFor($user, $turn);

        $this->postJson(
            '/api/v1/ai/events/batch',
            array_merge($args, ['dry_run' => false, 'action_id' => $turn->id])
        )->assertOk();

        $this->app['auth']->forgetGuards();

        $payload = $this->withoutHeader('Authorization')
            ->actingAs($user)
            ->getJson('/ai/actions/' . $turn->id)
            ->assertOk()
            ->json();

        $this->assertSame(AiActionService::STATUS_EXECUTED, $payload['status']);
        $this->assertNotNull($payload['batch']['id']);
        $this->assertNotNull($payload['batch']['undo_until']);
        $this->assertCount(3, $payload['batch']['links']);
    }

    #[Test]
    public function the_bar_endpoint_refuses_a_foreign_action(): void
    {
        $foreign = $this->pendingAction(User::factory()->create());

        $this->actingAs($this->planner())
            ->getJson('/ai/actions/' . $foreign->id)
            ->assertForbidden();
    }
}
