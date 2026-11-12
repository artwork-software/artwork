<?php

namespace Tests\Feature\AiAssistant;

use Artwork\Modules\AiAssistant\Listeners\AttachActivityOrigin;
use Artwork\Modules\AiAssistant\Models\AiActionBatch;
use Artwork\Modules\AiAssistant\Models\AiActionBatchItem;
use Artwork\Modules\AiAssistant\Models\AiConversation;
use Artwork\Modules\AiAssistant\Models\AiConversationTurn;
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
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Permission;
use Tests\Concerns\GrantsAiAccess;
use Tests\TestCase;

/**
 * core-undo: Batch, Revert, Konflikte, Frist (Konzept W3).
 */
final class AiUndoTest extends TestCase
{
    use GrantsAiAccess;

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

        $this->aiClient = Passport::client()->newQuery()->forceCreate([
            'name' => 'ai-session',
            'secret' => null,
            'provider' => config('auth.guards.api.provider'),
            'redirect_uris' => [],
            'grant_types' => ['ai_session'],
            'revoked' => false,
        ]);

        config(['ai.enabled' => true]);
        AiAssistantSettings::fake([
            'enabled' => true,
            'level_b_enabled' => true,
            'level_c_enabled' => true,
            'transcript_retention_days' => 0,
            'ai_session_client_id' => (string) $this->aiClient->getKey(),
        ]);

        $this->room = Room::factory()->create(['name' => 'Studio 1']);
        $this->eventType = EventType::factory()->create();
        $this->project = Project::factory()->create(['name' => 'Tosca']);
    }

    private function planner(): User
    {
        $user = User::factory()->create();

        foreach ([PermissionEnum::PROJECT_VIEW->value, PermissionEnum::CREATE_EVENTS_WITHOUT_REQUEST->value] as $name) {
            $user->givePermissionTo(Permission::findOrCreate($name, 'web'));
        }

        return $user->fresh();
    }

    private function event(string $start = '2026-09-14 19:00:00', string $end = '2026-09-14 21:00:00'): Event
    {
        return Event::factory()->create([
            'room_id' => $this->room->id,
            'project_id' => $this->project->id,
            'event_type_id' => $this->eventType->id,
            'eventName' => 'Vorstellung',
            'start_time' => $start,
            'end_time' => $end,
        ]);
    }

    /**
     * @param list<array{model: Event, action: string, before_state: array<string, mixed>|null}> $items
     */
    private function batchFor(User $user, array $items, ?CarbonImmutable $executedAt = null): AiActionBatch
    {
        $executedAt ??= CarbonImmutable::now();

        $batch = AiActionBatch::query()->create([
            'user_id' => $user->id,
            'conversation_id' => null,
            'tool' => 'create_events',
            'status' => AiActionBatch::STATUS_EXECUTED,
            'executed_at' => $executedAt,
            'undo_until' => $executedAt->addHours((int) config('ai.undo_hours')),
        ]);

        foreach ($items as $item) {
            $batch->items()->create([
                'model_type' => Event::class,
                'model_id' => $item['model']->id,
                'action' => $item['action'],
                'before_state' => $item['before_state'],
            ]);
        }

        return $batch->fresh('items');
    }

    // --- Revert der drei Aktionsarten ---------------------------------------------------------

    #[Test]
    public function reverting_a_creation_removes_the_events(): void
    {
        $user = $this->planner();
        $first = $this->event();
        $second = $this->event('2026-09-21 19:00:00', '2026-09-21 21:00:00');
        $batch = $this->batchFor($user, [
            ['model' => $first, 'action' => AiActionBatchItem::ACTION_CREATE, 'before_state' => null],
            ['model' => $second, 'action' => AiActionBatchItem::ACTION_CREATE, 'before_state' => null],
        ]);

        $this->actingAs($user)
            ->postJson('/ai/actions/batches/' . $batch->id . '/revert')
            ->assertOk()
            ->assertJsonPath('status', AiActionBatch::STATUS_REVERTED);

        $this->assertSame(0, Event::query()->whereIn('id', [$first->id, $second->id])->count());
        $this->assertSame(2, Event::withTrashed()->whereIn('id', [$first->id, $second->id])->count());
    }

    #[Test]
    public function reverting_an_update_writes_the_previous_state_back(): void
    {
        $user = $this->planner();
        $event = $this->event();
        $before = $event->getRawOriginal();

        $event->forceFill(['eventName' => 'Generalprobe', 'start_time' => '2026-09-14 20:00:00'])->save();

        $batch = $this->batchFor($user, [
            ['model' => $event, 'action' => AiActionBatchItem::ACTION_UPDATE, 'before_state' => $before],
        ]);

        $this->actingAs($user)->postJson('/ai/actions/batches/' . $batch->id . '/revert')->assertOk();

        $event = $event->fresh();
        $this->assertSame('Vorstellung', $event->eventName);
        $this->assertSame('19:00:00', $event->start_time->format('H:i:s'));
    }

    #[Test]
    public function reverting_a_deletion_restores_the_events(): void
    {
        $user = $this->planner();
        $event = $this->event();
        $before = $event->getRawOriginal();
        $event->delete();

        $batch = $this->batchFor($user, [
            ['model' => $event, 'action' => AiActionBatchItem::ACTION_DELETE, 'before_state' => $before],
        ]);

        $this->actingAs($user)->postJson('/ai/actions/batches/' . $batch->id . '/revert')->assertOk();

        $this->assertSame(1, Event::query()->whereKey($event->id)->count());
    }

    // --- Grenzen -----------------------------------------------------------------------------

    #[Test]
    public function a_foreign_batch_is_forbidden(): void
    {
        $batch = $this->batchFor(User::factory()->create(), [
            ['model' => $this->event(), 'action' => AiActionBatchItem::ACTION_CREATE, 'before_state' => null],
        ]);

        $this->actingAs($this->planner())
            ->postJson('/ai/actions/batches/' . $batch->id . '/revert')
            ->assertForbidden();
    }

    #[Test]
    public function an_expired_undo_window_is_refused(): void
    {
        $user = $this->planner();
        $event = $this->event();
        $batch = $this->batchFor(
            $user,
            [['model' => $event, 'action' => AiActionBatchItem::ACTION_CREATE, 'before_state' => null]],
            CarbonImmutable::now()->subHours((int) config('ai.undo_hours') + 1)
        );

        $this->actingAs($user)
            ->postJson('/ai/actions/batches/' . $batch->id . '/revert')
            ->assertStatus(422);

        $this->assertSame(1, Event::query()->whereKey($event->id)->count());
    }

    #[Test]
    public function a_batch_can_only_be_reverted_once(): void
    {
        $user = $this->planner();
        $batch = $this->batchFor($user, [
            ['model' => $this->event(), 'action' => AiActionBatchItem::ACTION_CREATE, 'before_state' => null],
        ]);

        $this->actingAs($user)->postJson('/ai/actions/batches/' . $batch->id . '/revert')->assertOk();
        $this->actingAs($user)->postJson('/ai/actions/batches/' . $batch->id . '/revert')->assertStatus(422);
    }

    #[Test]
    public function an_object_changed_in_the_meantime_blocks_the_revert(): void
    {
        $user = $this->planner();
        $event = $this->event();
        $batch = $this->batchFor(
            $user,
            [['model' => $event, 'action' => AiActionBatchItem::ACTION_CREATE, 'before_state' => null]],
            CarbonImmutable::now()->subHour()
        );

        // Jemand anders hat den Termin nach der Ausfuehrung angefasst.
        $event->forceFill(['eventName' => 'Von Hand geaendert'])->save();

        $this->actingAs($user)
            ->postJson('/ai/actions/batches/' . $batch->id . '/revert')
            ->assertStatus(409);

        $this->assertSame(1, Event::query()->whereKey($event->id)->count());
        $this->assertSame(AiActionBatch::STATUS_EXECUTED, $batch->fresh()->status);
    }

    #[Test]
    public function a_failure_in_the_third_item_leaves_everything_untouched(): void
    {
        $user = $this->planner();
        $first = $this->event();
        $second = $this->event('2026-09-21 19:00:00', '2026-09-21 21:00:00');
        $third = $this->event('2026-09-28 19:00:00', '2026-09-28 21:00:00');

        $batch = $this->batchFor($user, [
            ['model' => $first, 'action' => AiActionBatchItem::ACTION_CREATE, 'before_state' => null],
            ['model' => $second, 'action' => AiActionBatchItem::ACTION_CREATE, 'before_state' => null],
            // Kaputter Zustand: die Spalte gibt es nicht, das Zurueckschreiben scheitert.
            [
                'model' => $third,
                'action' => AiActionBatchItem::ACTION_UPDATE,
                'before_state' => ['spalte_die_es_nicht_gibt' => 'x'],
            ],
        ]);

        $this->actingAs($user);

        try {
            $this->postJson('/ai/actions/batches/' . $batch->id . '/revert');
        } catch (\Throwable) {
            // Der Fehler selbst ist hier nicht die Aussage — dass nichts halb passiert ist, schon.
        }

        $this->assertSame(3, Event::query()->whereIn('id', [$first->id, $second->id, $third->id])->count());
        $this->assertSame(AiActionBatch::STATUS_EXECUTED, $batch->fresh()->status);
    }

    // --- Herkunft und ganze Kette --------------------------------------------------------------

    #[Test]
    public function the_revert_is_logged_as_coming_from_the_assistant(): void
    {
        $user = $this->planner();
        $event = $this->event();
        $before = $event->getRawOriginal();
        $event->forceFill(['eventName' => 'Generalprobe'])->save();

        $batch = $this->batchFor($user, [
            ['model' => $event, 'action' => AiActionBatchItem::ACTION_UPDATE, 'before_state' => $before],
        ]);

        $this->actingAs($user)->postJson('/ai/actions/batches/' . $batch->id . '/revert')->assertOk();

        $activity = Activity::query()->where('subject_type', Event::class)->latest('id')->first();
        $this->assertNotNull($activity);
        $this->assertSame(AttachActivityOrigin::VIA_AI_ASSISTANT, $activity->properties['via']);
    }

    #[Test]
    public function an_executed_action_can_be_undone_end_to_end(): void
    {
        $user = $this->planner();
        $args = [
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

        $conversation = AiConversation::query()->create([
            'user_id' => $user->id,
            'title' => 'Serie anlegen',
            'status' => AiConversation::STATUS_AWAITING_CONFIRMATION,
        ]);
        $turn = $conversation->turns()->create([
            'client_turn_id' => (string) Str::uuid(),
            'user_message' => 'Lege die Vorstellungen an',
            'status' => AiConversationTurn::STATUS_AWAITING_CONFIRMATION,
            'tool_calls' => [],
            'context' => [],
            'pending_action' => [
                'tool' => 'create_events',
                'args' => $args,
                'args_hash' => AiActionHash::of($args),
                'preview' => ['summary' => '3 Termine anlegen', 'items' => []],
                'expires_at' => CarbonImmutable::now()->addMinutes(5)->toIso8601String(),
            ],
        ]);

        $token = $this->actingAs($this->grantAiAccess($user))->postJson('/ai/session', [
            'scopes' => [AiScopes::WRITE],
            'action_id' => $turn->id,
        ])->assertOk()->json('access_token');

        $this->app['auth']->forgetGuards();
        $result = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/ai/events/batch', array_merge($args, [
                'dry_run' => false,
                'action_id' => $turn->id,
            ]))->assertOk()->json();

        $this->assertNotNull($result['batch_id']);
        $this->assertNotNull($result['undo_until']);
        $this->assertCount(3, $result['links']);
        $this->assertSame(3, Event::query()->where('project_id', $this->project->id)->count());

        $this->app['auth']->forgetGuards();
        $this->withoutHeader('Authorization')
            ->actingAs($user)
            ->postJson('/ai/actions/batches/' . $result['batch_id'] . '/revert')
            ->assertOk();

        $this->assertSame(0, Event::query()->where('project_id', $this->project->id)->count());
    }
}
