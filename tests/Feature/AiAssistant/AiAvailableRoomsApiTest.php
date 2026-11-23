<?php

namespace Tests\Feature\AiAssistant;

use Artwork\Modules\AiAssistant\Support\AiScopes;
use Artwork\Modules\Area\Models\Area;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\EventType\Models\EventType;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\Room\Models\RoomCategory;
use Artwork\Modules\User\Models\User;
use Laravel\Passport\ClientRepository;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\GrantsAiAccess;
use Tests\TestCase;

/**
 * core-ai-aggregates: GET /ai/rooms/available — alle freien Raeume zu einem Zeitfenster (Entgrenzen P1).
 */
final class AiAvailableRoomsApiTest extends TestCase
{
    use GrantsAiAccess;

    private const WINDOW = 'from=2026-09-16T12:00:00&to=2026-09-16T13:00:00';

    private Area $area;

    protected function setUp(): void
    {
        parent::setUp();

        $this->enableAiAssistant();

        app(ClientRepository::class)
            ->createPersonalAccessGrantClient('PAT client', config('auth.guards.api.provider'));

        // Ein gemeinsames Areal, damit die Sortierung (Areal, dann Name) in den Faellen nur nach Name geht
        $this->area = Area::factory()->create(['name' => 'Testhaus']);
    }

    private function actingAsToken(User $user): User
    {
        $token = $this->grantAiAccess($user)->createToken('ai-session-test', [AiScopes::READ]);
        $this->withHeader('Authorization', 'Bearer ' . $token->accessToken);

        return $user;
    }

    private function room(string $name, array $attributes = []): Room
    {
        return Room::factory()->create([
            'name' => $name,
            'everyone_can_book' => true,
            'area_id' => $this->area->id,
            ...$attributes,
        ]);
    }

    private function eventIn(Room $room, string $start, string $end, array $attributes = []): Event
    {
        return Event::factory()->create([
            'room_id' => $room->id,
            'project_id' => Project::factory()->create()->id,
            'event_type_id' => EventType::factory()->create()->id,
            'start_time' => $start,
            'end_time' => $end,
            ...$attributes,
        ]);
    }

    /** @return list<string> */
    private function freeNames(string $query = self::WINDOW): array
    {
        $rooms = $this->getJson('/api/v1/ai/rooms/available?' . $query)->assertOk()->json('rooms');

        return array_column($rooms, 'name');
    }

    #[Test]
    public function available_rooms_lists_all_free_visible_rooms(): void
    {
        $this->room('Club');
        $this->room('Lager');
        $busy = $this->room('Saal');
        $this->eventIn($busy, '2026-09-16 11:30:00', '2026-09-16 12:30:00');
        // Angrenzende Termine blockieren nicht
        $club = Room::query()->where('name', 'Club')->firstOrFail();
        $this->eventIn($club, '2026-09-16 10:00:00', '2026-09-16 12:00:00');
        $this->actingAsToken(User::factory()->create());

        $payload = $this->getJson('/api/v1/ai/rooms/available?' . self::WINDOW)->assertOk()->json();

        $this->assertSame(['Club', 'Lager'], array_column($payload['rooms'], 'name'));
        $this->assertSame(2, $payload['total_free']);
        $this->assertSame(3, $payload['total_rooms']);
        $this->assertFalse($payload['truncated']);
        $this->assertStringStartsWith('2026-09-16T12:00:00', $payload['window']['from']);
        $this->assertStringStartsWith('2026-09-16T13:00:00', $payload['window']['to']);
        $this->assertArrayHasKey('area', $payload['rooms'][0]);
        $this->assertArrayHasKey('categories', $payload['rooms'][0]);
    }

    #[Test]
    public function available_rooms_accepts_at_and_duration(): void
    {
        $this->room('Club');
        $busy = $this->room('Saal');
        $this->eventIn($busy, '2026-09-16 13:00:00', '2026-09-16 14:00:00');
        $this->actingAsToken(User::factory()->create());

        $this->assertSame(['Club', 'Saal'], $this->freeNames('at=2026-09-16T12:00:00&duration_minutes=60'));
        $this->assertSame(['Club'], $this->freeNames('at=2026-09-16T12:00:00&duration_minutes=90'));
    }

    #[Test]
    public function available_rooms_respects_overnight_and_allday_events(): void
    {
        $this->room('Club');
        $overnight = $this->room('Bar');
        $allDay = $this->room('Saal');
        $this->eventIn($overnight, '2026-09-15 22:00:00', '2026-09-16 02:00:00');
        $this->eventIn($allDay, '2026-09-16 00:00:00', '2026-09-16 23:59:59', ['allDay' => true]);
        $this->actingAsToken(User::factory()->create());

        $this->assertSame(['Club'], $this->freeNames('from=2026-09-16T01:00:00&to=2026-09-16T03:00:00'));
        $this->assertSame(['Bar', 'Club'], $this->freeNames());
    }

    #[Test]
    public function available_rooms_blocks_on_invisible_events_without_details(): void
    {
        $this->room('Club');
        $foreign = $this->room('Saal');
        // Termin in einem fremden Projekt, das die Person nicht sehen darf
        $this->eventIn($foreign, '2026-09-16 12:15:00', '2026-09-16 12:45:00', ['name' => 'Geheime Probe']);
        $this->actingAsToken(User::factory()->create());

        $response = $this->getJson('/api/v1/ai/rooms/available?' . self::WINDOW)->assertOk();

        $this->assertSame(['Club'], array_column($response->json('rooms'), 'name'));
        $this->assertStringNotContainsString('Geheime Probe', $response->getContent());
    }

    #[Test]
    public function available_rooms_marks_requires_request(): void
    {
        $this->room('Club');
        $this->room('Saal', ['everyone_can_book' => false]);
        $this->actingAsToken(User::factory()->create());

        $rooms = collect($this->getJson('/api/v1/ai/rooms/available?' . self::WINDOW)->assertOk()->json('rooms'))
            ->keyBy('name');

        $this->assertFalse($rooms['Club']['requires_request']);
        $this->assertTrue($rooms['Saal']['requires_request']);
    }

    #[Test]
    public function available_rooms_filters_by_area_and_category(): void
    {
        $area = Area::factory()->create(['name' => 'Haupthaus']);
        $other = Area::factory()->create(['name' => 'Aussengelaende']);
        $category = RoomCategory::factory()->create(['name' => 'Meetingraum']);
        $this->room('Club', ['area_id' => $area->id])->categories()->attach($category->id);
        $this->room('Saal', ['area_id' => $area->id]);
        $this->room('Lager', ['area_id' => $other->id]);
        $this->actingAsToken(User::factory()->create());

        // Sortierung: Areal vor Name
        $this->assertSame(['Lager', 'Club', 'Saal'], $this->freeNames());
        $this->assertSame(['Club', 'Saal'], $this->freeNames(self::WINDOW . '&area_id=' . $area->id));
        $this->assertSame(['Club'], $this->freeNames(self::WINDOW . '&room_category_id=' . $category->id));
    }

    #[Test]
    public function available_rooms_skips_temporary_rooms_outside_their_period(): void
    {
        $this->room('Club');
        $this->room('Zelt', ['temporary' => true, 'start_date' => '2026-10-01', 'end_date' => '2026-10-31']);
        $this->actingAsToken(User::factory()->create());

        $this->assertSame(['Club'], $this->freeNames());
    }

    #[Test]
    public function available_rooms_rejects_window_over_24h(): void
    {
        $this->actingAsToken(User::factory()->create());

        $this->getJson('/api/v1/ai/rooms/available?from=2026-09-16T12:00:00&to=2026-09-17T12:01:00')
            ->assertStatus(422);
        $this->getJson('/api/v1/ai/rooms/available?at=2026-09-16T12:00:00&duration_minutes=1441')
            ->assertStatus(422);
        $this->getJson('/api/v1/ai/rooms/available')->assertStatus(422);
    }
}
