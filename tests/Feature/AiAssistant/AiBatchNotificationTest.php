<?php

namespace Tests\Feature\AiAssistant;

use Artwork\Modules\AiAssistant\Contracts\ActionGuard;
use Artwork\Modules\AiAssistant\Models\AiActionBatch;
use Artwork\Modules\AiAssistant\Settings\AiAssistantSettings;
use Artwork\Modules\AiAssistant\Support\AiScopes;
use Artwork\Modules\Event\Events\OccupancyUpdated;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\EventType\Models\EventType;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftQualification;
use Artwork\Modules\User\Models\User;
use Illuminate\Support\Facades\Event as EventFacade;
use Illuminate\Support\Str;
use Laravel\Passport\ClientRepository;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;
use Tests\Concerns\GrantsAiAccess;

/**
 * core-notification-buendelung (W4): eine Sammelmeldung je Batch und Empfaenger, ein
 * Refresh-Broadcast je Batch.
 */
final class AiBatchNotificationTest extends TestCase
{
    use GrantsAiAccess;

    private const MONDAY = '2026-09-14';

    private Room $room;

    private EventType $eventType;

    private Project $project;

    private User $manager;

    protected function setUp(): void
    {
        parent::setUp();

        $client = app(ClientRepository::class)
            ->createPersonalAccessGrantClient('PAT client', config('auth.guards.api.provider'));

        config(['ai.enabled' => true]);
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

        $this->manager = User::factory()->create();
        $this->project->users()->attach($this->manager->id, ['is_manager' => true]);

        $this->fakeActionGuard();
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

    /** @param string[] $scopes */
    private function actingAsToken(User $user, array $scopes = [AiScopes::WRITE]): void
    {
        $this->app['auth']->forgetGuards();

        $token = $this->grantAiAccess($user)->createToken('ai-session-test', $scopes);
        $this->withHeader('Authorization', 'Bearer ' . $token->accessToken);
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

    /** @return array<string, mixed> */
    private function execution(array $payload): array
    {
        return array_merge($payload, ['dry_run' => false, 'action_id' => (string) Str::uuid()]);
    }

    private function existingEvent(?User $creator = null): Event
    {
        return Event::factory()->create([
            'room_id' => $this->room->id,
            'project_id' => $this->project->id,
            'event_type_id' => $this->eventType->id,
            'start_time' => self::MONDAY . ' 19:00:00',
            'end_time' => self::MONDAY . ' 21:00:00',
            'user_id' => $creator?->id,
        ]);
    }

    /** @return array<int, string> */
    private function titlesFor(User $user): array
    {
        return $user->notifications()
            ->get()
            ->map(static fn ($notification): string => (string) ($notification->data['title'] ?? ''))
            ->all();
    }

    // --- Serie -------------------------------------------------------------------------------

    #[Test]
    public function a_series_sends_exactly_one_bundled_notification_per_recipient(): void
    {
        EventFacade::fake([OccupancyUpdated::class]);

        $planner = $this->planner();
        $this->actingAsToken($planner);

        $this->postJson('/api/v1/ai/events/batch', $this->execution($this->seriesPayload()))->assertOk();

        $this->assertSame(3, Event::query()->where('project_id', $this->project->id)->count());
        $this->assertSame(['3 Termine angelegt (via Assistent)'], $this->titlesFor($this->manager));

        EventFacade::assertDispatchedTimes(OccupancyUpdated::class, 1);
    }

    #[Test]
    public function the_causer_is_not_notified(): void
    {
        $planner = $this->planner();
        $this->project->users()->attach($planner->id, ['is_manager' => true]);
        $this->actingAsToken($planner);

        $this->postJson('/api/v1/ai/events/batch', $this->execution($this->seriesPayload()))->assertOk();

        $this->assertSame(0, $planner->notifications()->count());
        $this->assertSame(1, $this->manager->notifications()->count());
    }

    #[Test]
    public function people_assigned_to_a_shift_of_an_affected_event_are_notified(): void
    {
        $worker = User::factory()->create();
        $event = $this->existingEvent();
        $shift = Shift::factory()->create(['event_id' => $event->id]);
        $shift->users()->attach($worker->id, [
            'shift_qualification_id' => ShiftQualification::factory()->create()->id,
            'shift_count' => 1,
        ]);

        $this->actingAsToken($this->planner());

        $this->patchJson(
            '/api/v1/ai/events/' . $event->id,
            $this->execution(['name' => 'Neuer Name'])
        )->assertOk();

        $this->assertSame(['Termin geändert (via Assistent)'], $this->titlesFor($worker));
    }

    // --- Einzelaktion ------------------------------------------------------------------------

    #[Test]
    public function a_single_change_sends_a_single_notification_not_a_bundled_one(): void
    {
        $event = $this->existingEvent();
        $this->actingAsToken($this->planner());

        $this->patchJson(
            '/api/v1/ai/events/' . $event->id,
            $this->execution(['name' => 'Neuer Name'])
        )->assertOk();

        $this->assertSame(['Termin geändert (via Assistent)'], $this->titlesFor($this->manager));
    }

    // --- Loeschung ---------------------------------------------------------------------------

    /**
     * Die Empfaenger muessen die Loeschung ueberleben: nach `bulkDeleteEvent()` sind die Zeilen
     * weg, die Beziehungen haengen nur noch am Modell im Speicher.
     */
    #[Test]
    public function deleting_events_still_reaches_the_project_management(): void
    {
        $first = $this->existingEvent();
        $second = $this->existingEvent();

        $this->actingAsToken($this->planner());

        $this->deleteJson(
            '/api/v1/ai/events/batch',
            $this->execution(['ids' => [$first->id, $second->id]])
        )->assertOk();

        $this->assertSame(['2 Termine gelöscht (via Assistent)'], $this->titlesFor($this->manager));
    }

    // --- Ruecknahme --------------------------------------------------------------------------

    #[Test]
    public function reverting_a_batch_sends_one_bundled_notification(): void
    {
        $planner = $this->planner();
        $this->actingAsToken($planner);

        $this->postJson('/api/v1/ai/events/batch', $this->execution($this->seriesPayload()))->assertOk();

        $batch = AiActionBatch::query()->where('user_id', $planner->id)->firstOrFail();
        $this->manager->notifications()->delete();

        // Der Bearer-Header des Schreibaufrufs wuerde sonst am Web-Endpunkt mitlaufen und dort
        // gegen den falschen Guard authentifizieren.
        $this->flushHeaders();

        EventFacade::fake([OccupancyUpdated::class]);

        $this->actingAs($planner)
            ->postJson('/ai/actions/batches/' . $batch->id . '/revert')
            ->assertOk();

        $this->assertSame(['3 Termine rückgängig gemacht (via Assistent)'], $this->titlesFor($this->manager));

        EventFacade::assertDispatchedTimes(OccupancyUpdated::class, 1);
    }

    // --- Vorschau ----------------------------------------------------------------------------

    #[Test]
    public function a_preview_notifies_nobody(): void
    {
        EventFacade::fake([OccupancyUpdated::class]);

        $this->actingAsToken($this->planner());

        $this->postJson('/api/v1/ai/events/batch', $this->seriesPayload())->assertOk();

        $this->assertSame(0, $this->manager->notifications()->count());

        EventFacade::assertNotDispatched(OccupancyUpdated::class);
    }
}
