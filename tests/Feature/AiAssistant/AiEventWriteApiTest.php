<?php

namespace Tests\Feature\AiAssistant;

use Artwork\Modules\AiAssistant\Contracts\ActionGuard;
use Artwork\Modules\AiAssistant\DTOs\AiWriteConflictData;
use Artwork\Modules\AiAssistant\Listeners\AttachActivityOrigin;
use Artwork\Modules\AiAssistant\Settings\AiAssistantSettings;
use Artwork\Modules\AiAssistant\Support\AiScopes;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\EventType\Models\EventType;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\User\Models\User;
use Illuminate\Support\Str;
use Laravel\Passport\ClientRepository;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * core-write-endpoints-dry-run: POST /ai/events/batch, PATCH /ai/events/{id},
 * DELETE /ai/events/batch (Konzept W1).
 */
final class AiEventWriteApiTest extends TestCase
{
    /** Montag */
    private const MONDAY = '2026-09-14';

    private Room $room;

    private EventType $eventType;

    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $client = app(ClientRepository::class)
            ->createPersonalAccessGrantClient('PAT client', config('auth.guards.api.provider'));

        // Der Herkunfts-Listener erkennt KI-Tokens an der Client-ID aus dem JWT — fuer den Test
        // gilt der Personal-Access-Client als KI-Client (core-via-activity-log).
        AiAssistantSettings::fake([
            'enabled' => true,
            'level_b_enabled' => true,
            'level_c_enabled' => true,
            'transcript_retention_days' => 0,
            'ai_session_client_id' => (string) $client->getKey(),
        ]);

        $this->room = Room::factory()->create(['name' => 'Studio 1']);
        $this->eventType = EventType::factory()->create();
        $this->project = Project::factory()->create(['name' => 'Tosca']);
    }

    /** @param string[] $scopes */
    private function actingAsToken(User $user, array $scopes = [AiScopes::WRITE]): void
    {
        $this->app['auth']->forgetGuards();

        $token = $user->createToken('ai-session-test', $scopes);
        $this->withHeader('Authorization', 'Bearer ' . $token->accessToken);
    }

    /** @param string[] $permissions */
    private function userWith(array $permissions): User
    {
        $user = User::factory()->create();

        foreach ($permissions as $permission) {
            $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }

        return $user->fresh();
    }

    private function planner(): User
    {
        return $this->userWith([
            PermissionEnum::PROJECT_VIEW->value,
            PermissionEnum::CREATE_EVENTS_WITHOUT_REQUEST->value,
        ]);
    }

    /**
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    private function seriesPayload(array $overrides = []): array
    {
        return array_merge([
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
        ], $overrides);
    }

    private function existingEvent(string $start, string $end): Event
    {
        return Event::factory()->create([
            'room_id' => $this->room->id,
            'project_id' => $this->project->id,
            'event_type_id' => $this->eventType->id,
            'start_time' => $start,
            'end_time' => $end,
        ]);
    }

    private function fakeActionGuard(): void
    {
        $this->app->bind(ActionGuard::class, fn (): ActionGuard => new class implements ActionGuard {
            /** @param array<string, mixed> $args */
            public function assertExecutable(User $user, string $actionId, string $tool, array $args): void
            {
            }

            public function complete(User $user, string $actionId, ?string $batchId): void
            {
            }
        });
    }

    // --- Scope, Sichtbarkeit, Pflichtfelder --------------------------------------------------

    #[Test]
    public function a_token_with_neither_scope_is_rejected(): void
    {
        $this->actingAsToken($this->planner(), [AiScopes::TRANSCRIPT]);

        $this->postJson('/api/v1/ai/events/batch', $this->seriesPayload())->assertForbidden();
        $this->patchJson('/api/v1/ai/events/1', ['name' => 'x'])->assertForbidden();
        $this->deleteJson('/api/v1/ai/events/batch', ['ids' => [1]])->assertForbidden();
    }

    /**
     * Nachtrag aus core-confirm-actions: die Vorschau darf auch ein Read-Token holen (artwork-ai
     * hat vor der Bestaetigung nur eines), die Ausfuehrung nicht.
     */
    #[Test]
    public function a_read_token_may_preview_but_not_execute(): void
    {
        $this->actingAsToken($this->planner(), [AiScopes::READ]);

        $this->postJson('/api/v1/ai/events/batch', $this->seriesPayload())->assertOk();

        $this->postJson(
            '/api/v1/ai/events/batch',
            $this->seriesPayload(['dry_run' => false, 'action_id' => (string) Str::uuid()])
        )->assertForbidden();

        $this->assertSame(0, Event::query()->where('project_id', $this->project->id)->count());
    }

    #[Test]
    public function an_invisible_project_is_a_404(): void
    {
        $this->actingAsToken(User::factory()->create());

        $this->postJson('/api/v1/ai/events/batch', $this->seriesPayload())->assertNotFound();
    }

    #[Test]
    public function an_unknown_event_is_a_404(): void
    {
        $this->actingAsToken($this->planner());

        $this->patchJson('/api/v1/ai/events/999999', ['name' => 'x'])->assertNotFound();
        $this->deleteJson('/api/v1/ai/events/batch', ['ids' => [999999]])->assertNotFound();
    }

    #[Test]
    public function a_series_longer_than_the_maximum_period_is_rejected(): void
    {
        $this->actingAsToken($this->planner());

        $payload = $this->seriesPayload();
        $payload['series_spec']['to'] = '2027-09-28';

        $this->postJson('/api/v1/ai/events/batch', $payload)->assertStatus(422);
    }

    // --- Vorschau ----------------------------------------------------------------------------

    #[Test]
    public function the_preview_leaves_the_database_untouched(): void
    {
        $before = Event::query()->count();
        $this->actingAsToken($this->planner());

        $payload = $this->postJson('/api/v1/ai/events/batch', $this->seriesPayload())->assertOk()->json();

        $this->assertTrue($payload['dry_run']);
        $this->assertSame('create_events', $payload['tool']);
        $this->assertCount(3, $payload['items']);
        $this->assertSame(3, $payload['summary']['total']);
        $this->assertSame('Vorstellung', $payload['items'][0]['label']);
        $this->assertStringStartsWith(self::MONDAY . 'T19:00:00', $payload['items'][0]['start']);
        $this->assertStringStartsWith(self::MONDAY . 'T21:00:00', $payload['items'][0]['end']);
        $this->assertSame($this->room->id, $payload['items'][0]['room']['id']);
        $this->assertSame($before, Event::query()->count());
    }

    #[Test]
    public function the_preview_reports_a_room_collision(): void
    {
        $collision = $this->existingEvent('2026-09-14 19:30:00', '2026-09-14 21:30:00');
        $this->actingAsToken($this->planner());

        $payload = $this->postJson('/api/v1/ai/events/batch', $this->seriesPayload())->assertOk()->json();

        $first = $payload['items'][0];
        $this->assertSame(AiWriteConflictData::ROOM_COLLISION, $first['conflicts'][0]['type']);
        $this->assertSame($collision->id, $first['conflicts'][0]['event']['id']);
        $this->assertSame(1, $payload['summary']['with_conflicts']);
        $this->assertSame(0, $payload['summary']['blocked']);
    }

    #[Test]
    public function the_preview_reports_a_missing_permission(): void
    {
        $this->actingAsToken($this->userWith([PermissionEnum::PROJECT_VIEW->value]));

        $payload = $this->postJson('/api/v1/ai/events/batch', $this->seriesPayload())->assertOk()->json();

        $this->assertSame(AiWriteConflictData::MISSING_PERMISSION, $payload['items'][0]['conflicts'][0]['type']);
        $this->assertSame(3, $payload['summary']['blocked']);
        $this->assertSame(0, Event::query()->where('project_id', $this->project->id)->count());
    }

    #[Test]
    public function the_update_preview_shows_the_new_times_without_saving_them(): void
    {
        $event = $this->existingEvent('2026-09-14 19:00:00', '2026-09-14 21:00:00');
        $this->actingAsToken($this->planner());

        $payload = $this->patchJson('/api/v1/ai/events/' . $event->id, [
            'start_time' => '20:00',
            'end_time' => '22:00',
        ])->assertOk()->json();

        $this->assertTrue($payload['dry_run']);
        $this->assertStringStartsWith(self::MONDAY . 'T20:00:00', $payload['items'][0]['start']);
        $this->assertSame('19:00:00', $event->fresh()->start_time->format('H:i:s'));
    }

    #[Test]
    public function the_delete_preview_keeps_the_events(): void
    {
        $first = $this->existingEvent('2026-09-14 19:00:00', '2026-09-14 21:00:00');
        $second = $this->existingEvent('2026-09-21 19:00:00', '2026-09-21 21:00:00');
        $this->actingAsToken($this->planner());

        $payload = $this->deleteJson('/api/v1/ai/events/batch', ['ids' => [$first->id, $second->id]])
            ->assertOk()
            ->json();

        $this->assertSame('delete_events', $payload['tool']);
        $this->assertCount(2, $payload['items']);
        $this->assertSame(2, Event::query()->whereIn('id', [$first->id, $second->id])->count());
    }

    // --- Ausfuehrung -------------------------------------------------------------------------

    #[Test]
    public function an_execution_without_an_action_id_is_rejected(): void
    {
        $this->actingAsToken($this->planner());

        $payload = $this->seriesPayload(['dry_run' => false]);

        $this->postJson('/api/v1/ai/events/batch', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors('action_id');
    }

    /** Seit core-confirm-actions faellt eine unbekannte action_id im echten ActionGuard durch. */
    #[Test]
    public function an_execution_without_a_confirmed_action_is_rejected(): void
    {
        $this->actingAsToken($this->planner());

        $payload = $this->seriesPayload(['dry_run' => false, 'action_id' => (string) Str::uuid()]);

        $this->postJson('/api/v1/ai/events/batch', $payload)->assertStatus(422);
        $this->assertSame(0, Event::query()->where('project_id', $this->project->id)->count());
    }

    #[Test]
    public function an_execution_without_the_permission_is_forbidden(): void
    {
        $this->fakeActionGuard();
        $this->actingAsToken($this->userWith([PermissionEnum::PROJECT_VIEW->value]));

        $payload = $this->seriesPayload(['dry_run' => false, 'action_id' => (string) Str::uuid()]);

        $this->postJson('/api/v1/ai/events/batch', $payload)->assertForbidden();
        $this->assertSame(0, Event::query()->where('project_id', $this->project->id)->count());
    }

    #[Test]
    public function an_execution_creates_the_events_and_records_the_origin(): void
    {
        $this->fakeActionGuard();
        $conversationId = (string) Str::uuid();
        $this->actingAsToken($this->planner());
        $this->withHeader(AttachActivityOrigin::CONVERSATION_HEADER, $conversationId);

        $payload = $this->postJson(
            '/api/v1/ai/events/batch',
            $this->seriesPayload(['dry_run' => false, 'action_id' => (string) Str::uuid()])
        )->assertOk()->json();

        $this->assertFalse($payload['dry_run']);
        $this->assertCount(3, $payload['ids']);
        // Seit core-undo schreibt der BatchRecorder einen echten Batch mit Undo-Frist.
        $this->assertNotNull($payload['batch_id']);
        $this->assertNotNull($payload['undo_until']);
        $this->assertCount(3, $payload['links']);
        $this->assertSame(3, Event::query()->where('project_id', $this->project->id)->count());

        $activity = Activity::query()->where('subject_type', Event::class)->latest('id')->first();
        $this->assertNotNull($activity);
        $this->assertSame(AttachActivityOrigin::VIA_AI_ASSISTANT, $activity->properties['via']);
        $this->assertSame($conversationId, $activity->properties['via_detail']);
    }

    #[Test]
    public function an_execution_updates_a_single_event(): void
    {
        $this->fakeActionGuard();
        $event = $this->existingEvent('2026-09-14 19:00:00', '2026-09-14 21:00:00');
        $this->actingAsToken($this->planner());

        $this->patchJson('/api/v1/ai/events/' . $event->id, [
            'start_time' => '20:00',
            'end_time' => '22:00',
            'dry_run' => false,
            'action_id' => (string) Str::uuid(),
        ])->assertOk();

        $this->assertSame('20:00:00', $event->fresh()->start_time->format('H:i:s'));
    }

    #[Test]
    public function an_execution_deletes_a_batch(): void
    {
        $this->fakeActionGuard();
        $first = $this->existingEvent('2026-09-14 19:00:00', '2026-09-14 21:00:00');
        $second = $this->existingEvent('2026-09-21 19:00:00', '2026-09-21 21:00:00');
        $this->actingAsToken($this->planner());

        $this->deleteJson('/api/v1/ai/events/batch', [
            'ids' => [$first->id, $second->id],
            'dry_run' => false,
            'action_id' => (string) Str::uuid(),
        ])->assertOk();

        $this->assertSame(0, Event::query()->whereIn('id', [$first->id, $second->id])->count());
    }
}
