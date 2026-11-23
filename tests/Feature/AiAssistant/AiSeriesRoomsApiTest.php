<?php

namespace Tests\Feature\AiAssistant;

use Artwork\Modules\AiAssistant\Contracts\ActionGuard;
use Artwork\Modules\AiAssistant\DTOs\AiWriteConflictData;
use Artwork\Modules\AiAssistant\Settings\AiAssistantSettings;
use Artwork\Modules\AiAssistant\Support\AiScopes;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\EventType\Models\EventType;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\Room\Models\RoomCategory;
use Artwork\Modules\User\Models\User;
use Illuminate\Support\Str;
use Laravel\Passport\ClientRepository;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Tests\Concerns\GrantsAiAccess;
use Tests\TestCase;

/**
 * core-ai-series-rooms: Serie ohne Projekt, in mehreren Raeumen, Raeume nach Kategorie
 * (Feedback 17.11.: „Serientermin in 3 Raeumen mit Kategorie Meetingraum ... kein Projekt").
 */
final class AiSeriesRoomsApiTest extends TestCase
{
    use GrantsAiAccess;

    private EventType $type;

    /** @var list<Room> */
    private array $meetingRooms;

    private Room $stage;

    protected function setUp(): void
    {
        parent::setUp();

        $client = app(ClientRepository::class)
            ->createPersonalAccessGrantClient('PAT client', config('auth.guards.api.provider'));

        AiAssistantSettings::fake([
            'enabled' => true,
            'level_b_enabled' => true,
            'level_c_enabled' => true,
            'transcript_retention_days' => 0,
            'ai_session_client_id' => (string) $client->getKey(),
        ]);

        $this->type = EventType::factory()->create(['name' => 'Blocker']);
        $meeting = RoomCategory::query()->create(['name' => 'Meetingraum']);
        $workshop = RoomCategory::query()->create(['name' => 'Werkstatt']);

        $this->meetingRooms = [];
        foreach (['Meeting A', 'Meeting B', 'Meeting C'] as $name) {
            $room = Room::factory()->create(['name' => $name]);
            $room->categories()->attach($meeting->id);
            $this->meetingRooms[] = $room;
        }
        $this->stage = Room::factory()->create(['name' => 'Bühne']);
        $this->stage->categories()->attach($workshop->id);
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
        foreach ([PermissionEnum::PROJECT_VIEW->value, PermissionEnum::CREATE_EVENTS_WITHOUT_REQUEST->value] as $permission) {
            $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }

        return $user->fresh();
    }

    /**
     * Die Serie aus dem Feedback: Di 01.12. bis Fr 25.12.2026, Mo–Fr 10–14 Uhr, ohne Projekt.
     *
     * @param array<string, mixed> $spec
     * @return array<string, mixed>
     */
    private function payload(array $spec = []): array
    {
        return [
            'series_spec' => array_merge([
                'from' => '2026-12-01',
                'to' => '2026-12-25',
                'weekdays' => [1, 2, 3, 4, 5],
                'start_time' => '10:00',
                'duration_minutes' => 240,
                'room_ids' => array_map(fn (Room $room): int => $room->id, $this->meetingRooms),
                'type_id' => $this->type->id,
                'name' => 'Hanz',
            ], $spec),
        ];
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

    #[Test]
    public function rooms_can_be_filtered_by_category_name_and_id(): void
    {
        $this->actingAsToken(User::factory()->create(), [AiScopes::READ]);

        $byName = $this->getJson('/api/v1/ai/rooms?category=meeting')->assertOk()->json();
        $this->assertSame(['Meeting A', 'Meeting B', 'Meeting C'], array_column($byName['data'], 'name'));
        $this->assertSame(['Meetingraum'], $byName['data'][0]['categories']);

        $workshopId = RoomCategory::query()->where('name', 'Werkstatt')->value('id');
        $byId = $this->getJson('/api/v1/ai/rooms?category_id=' . $workshopId)->assertOk()->json();
        $this->assertSame(['Bühne'], array_column($byId['data'], 'name'));
    }

    #[Test]
    public function a_series_without_project_in_three_rooms_is_previewed_per_room(): void
    {
        $this->actingAsToken($this->planner());

        $payload = $this->postJson('/api/v1/ai/events/batch', $this->payload())->assertOk()->json();

        // 19 Werktage (01.–25.12.2026, Di bis Fr der letzten Woche eingeschlossen) x 3 Raeume
        $this->assertTrue($payload['dry_run']);
        $this->assertCount(57, $payload['items']);
        $this->assertSame(
            array_map(fn (Room $room): int => $room->id, $this->meetingRooms),
            array_values(array_unique(array_column(array_column($payload['items'], 'room'), 'id')))
        );
        $this->assertStringStartsWith('2026-12-01T10:00:00', $payload['items'][0]['start']);
        $this->assertSame(0, Event::query()->count(), 'Vorschau schreibt nichts');
    }

    #[Test]
    public function the_execution_creates_events_without_project(): void
    {
        $this->fakeActionGuard();
        $this->actingAsToken($this->planner());

        $payload = $this->postJson(
            '/api/v1/ai/events/batch',
            $this->payload(['to' => '2026-12-02']) + ['dry_run' => false, 'action_id' => (string) Str::uuid()]
        )->assertOk()->json();

        $this->assertCount(6, $payload['ids']);
        $events = Event::query()->whereKey($payload['ids'])->get();
        $this->assertSame([null], $events->pluck('project_id')->unique()->values()->all());
        $this->assertSame(['Hanz'], $events->pluck('eventName')->unique()->values()->all());
        $this->assertCount(3, $events->pluck('room_id')->unique());
    }

    #[Test]
    public function a_single_room_id_still_works(): void
    {
        $this->actingAsToken($this->planner());
        $spec = $this->payload(['to' => '2026-12-04'])['series_spec'];
        unset($spec['room_ids']);
        $spec['room_id'] = $this->stage->id;

        $payload = $this->postJson('/api/v1/ai/events/batch', ['series_spec' => $spec])->assertOk()->json();

        $this->assertCount(4, $payload['items']);
        $this->assertSame($this->stage->id, $payload['items'][0]['room']['id']);
    }

    #[Test]
    public function more_than_the_series_limit_across_rooms_is_rejected(): void
    {
        config(['ai.series_limit' => 50]);
        $this->actingAsToken($this->planner());

        $this->postJson('/api/v1/ai/events/batch', $this->payload())->assertStatus(422);
    }

    #[Test]
    public function unknown_rooms_and_missing_rooms_are_rejected(): void
    {
        $this->actingAsToken($this->planner());

        $this->postJson('/api/v1/ai/events/batch', $this->payload(['room_ids' => [$this->stage->id, 999999]]))
            ->assertNotFound();

        $spec = $this->payload()['series_spec'];
        unset($spec['room_ids']);
        $this->postJson('/api/v1/ai/events/batch', ['series_spec' => $spec])
            ->assertStatus(422)->assertJsonValidationErrors(['series_spec.room_id']);
    }

    #[Test]
    public function a_missing_room_permission_blocks_only_that_room(): void
    {
        $user = User::factory()->create();
        // Raumanfragen erlaubt (Policy `create`), aber nicht „ohne Anfrage anlegen": entscheidend ist der Raum.
        foreach ([PermissionEnum::PROJECT_VIEW->value, PermissionEnum::EVENT_REQUEST->value] as $permission) {
            $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }
        $this->meetingRooms[0]->update(['everyone_can_book' => true]);
        $this->actingAsToken($user->fresh());

        $payload = $this->postJson('/api/v1/ai/events/batch', $this->payload(['to' => '2026-12-01']))->assertOk()->json();

        $conflicts = array_map(fn (array $item): array => array_column($item['conflicts'], 'type'), $payload['items']);
        $this->assertSame([[], [AiWriteConflictData::MISSING_PERMISSION], [AiWriteConflictData::MISSING_PERMISSION]], $conflicts);
    }
}
