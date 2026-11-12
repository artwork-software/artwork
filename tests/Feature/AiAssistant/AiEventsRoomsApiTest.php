<?php

namespace Tests\Feature\AiAssistant;

use Artwork\Modules\AiAssistant\Support\AiScopes;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\EventType\Models\EventType;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\User\Models\User;
use Carbon\CarbonImmutable;
use Laravel\Passport\ClientRepository;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Concerns\GrantsAiAccess;

/**
 * core-api-events-rooms: GET /ai/events, /ai/rooms/occupancy, /ai/rooms/free-slots
 * (Konzept B1 §3–§5, §8).
 */
final class AiEventsRoomsApiTest extends TestCase
{
    use GrantsAiAccess;

    private CarbonImmutable $day;

    protected function setUp(): void
    {
        parent::setUp();

        app(ClientRepository::class)
            ->createPersonalAccessGrantClient('PAT client', config('auth.guards.api.provider'));

        // Ein Mittwoch, weit genug weg von „heute", damit die Faelle stabil bleiben.
        $this->day = CarbonImmutable::parse('2026-09-16 00:00:00');
    }

    /** @param string[] $scopes */
    private function actingAsToken(User $user, array $scopes = [AiScopes::READ]): void
    {
        $token = $this->grantAiAccess($user)->createToken('ai-session-test', $scopes);
        $this->withHeader('Authorization', 'Bearer ' . $token->accessToken);
    }

    private function eventIn(Room $room, string $start, string $end, ?Project $project = null): Event
    {
        return Event::factory()->create([
            'room_id' => $room->id,
            'project_id' => ($project ?? Project::factory()->create())->id,
            'event_type_id' => EventType::factory()->create()->id,
            'start_time' => $start,
            'end_time' => $end,
        ]);
    }

    private function range(): string
    {
        return 'from=' . $this->day->toDateString() . '&to=' . $this->day->toDateString();
    }

    // --- Scope und Zeitraum ----------------------------------------------------------------

    #[Test]
    public function a_token_without_the_read_scope_is_rejected(): void
    {
        $this->actingAsToken(User::factory()->create(), [AiScopes::TRANSCRIPT]);

        $this->getJson('/api/v1/ai/events?' . $this->range())->assertForbidden();
        $this->getJson('/api/v1/ai/rooms/occupancy?' . $this->range())->assertForbidden();
        $this->getJson('/api/v1/ai/rooms/free-slots?' . $this->range())->assertForbidden();
    }

    #[Test]
    public function every_endpoint_demands_a_period(): void
    {
        $this->actingAsToken(User::factory()->create());

        $this->getJson('/api/v1/ai/events')->assertStatus(422);
        $this->getJson('/api/v1/ai/rooms/occupancy')->assertStatus(422);
        $this->getJson('/api/v1/ai/rooms/free-slots')->assertStatus(422);
    }

    #[Test]
    public function a_period_longer_than_the_maximum_is_rejected(): void
    {
        $this->actingAsToken(User::factory()->create());

        $to = $this->day->addDays((int) config('ai.max_range_days') + 1);
        $range = 'from=' . $this->day->toDateString() . '&to=' . $to->toDateString();

        $this->getJson('/api/v1/ai/events?' . $range)->assertStatus(422);
        $this->getJson('/api/v1/ai/rooms/occupancy?' . $range)->assertStatus(422);
        $this->getJson('/api/v1/ai/rooms/free-slots?' . $range)->assertStatus(422);
    }

    // --- events ----------------------------------------------------------------------------

    #[Test]
    public function the_event_list_returns_flat_references_and_house_time(): void
    {
        $room = Room::factory()->create(['name' => 'Studio 1']);
        $event = $this->eventIn($room, '2026-09-16 10:00:00', '2026-09-16 12:00:00');
        $this->actingAsToken(User::factory()->create());

        $payload = $this->getJson('/api/v1/ai/events?' . $this->range())->assertOk()->json();

        $this->assertSame(1, $payload['total']);
        $this->assertSame($event->id, $payload['data'][0]['id']);
        $this->assertSame($room->id, $payload['data'][0]['room']['id']);
        $this->assertSame('Studio 1', $payload['data'][0]['room']['name']);
        $this->assertNotNull($payload['data'][0]['project']['id']);
        $this->assertStringStartsWith('2026-09-16T10:00:00', $payload['data'][0]['start']);
    }

    #[Test]
    public function the_event_list_honours_room_project_and_type_filters(): void
    {
        $roomA = Room::factory()->create();
        $roomB = Room::factory()->create();
        $wanted = $this->eventIn($roomA, '2026-09-16 10:00:00', '2026-09-16 11:00:00');
        $this->eventIn($roomB, '2026-09-16 10:00:00', '2026-09-16 11:00:00');
        $this->actingAsToken(User::factory()->create());

        $byRoom = $this->getJson('/api/v1/ai/events?' . $this->range() . '&room_ids[]=' . $roomA->id)
            ->assertOk()->json();
        $this->assertSame(1, $byRoom['total']);
        $this->assertSame($wanted->id, $byRoom['data'][0]['id']);

        $byProject = $this->getJson('/api/v1/ai/events?' . $this->range() . '&project_id=' . $wanted->project_id)
            ->assertOk()->json();
        $this->assertSame(1, $byProject['total']);

        $byType = $this->getJson('/api/v1/ai/events?' . $this->range() . '&type_id=' . $wanted->event_type_id)
            ->assertOk()->json();
        $this->assertSame(1, $byType['total']);
    }

    #[Test]
    public function the_event_list_is_capped_and_marks_itself_as_truncated(): void
    {
        $room = Room::factory()->create();
        $this->eventIn($room, '2026-09-16 09:00:00', '2026-09-16 10:00:00');
        $this->eventIn($room, '2026-09-16 11:00:00', '2026-09-16 12:00:00');
        $this->actingAsToken(User::factory()->create());

        $payload = $this->getJson('/api/v1/ai/events?' . $this->range() . '&limit=1')->assertOk()->json();

        $this->assertCount(1, $payload['data']);
        $this->assertSame(2, $payload['total']);
        $this->assertTrue($payload['truncated']);
    }

    #[Test]
    public function event_names_are_truncated(): void
    {
        config(['ai.text_limit' => 20]);
        $room = Room::factory()->create();
        $event = $this->eventIn($room, '2026-09-16 10:00:00', '2026-09-16 11:00:00');
        $event->update(['eventName' => str_repeat('b', 100)]);
        $this->actingAsToken(User::factory()->create());

        $name = $this->getJson('/api/v1/ai/events?' . $this->range())->assertOk()->json('data.0.name');

        $this->assertLessThanOrEqual(21, mb_strlen($name));
    }

    /**
     * Die Oberflaeche zeigt jeder angemeldeten Person alle Termine (core-visibility-scopes §1) —
     * der Endpunkt tut dasselbe. Wird die Regel spaeter enger, faellt dieser Test auf.
     */
    #[Test]
    public function events_follow_the_visibility_rule_of_the_calendar(): void
    {
        $room = Room::factory()->create();
        $foreignProject = Project::factory()->create();
        $this->eventIn($room, '2026-09-16 10:00:00', '2026-09-16 11:00:00', $foreignProject);

        $outsider = User::factory()->create();
        $this->actingAsToken($outsider);

        $this->assertSame(
            Event::query()->visibleFor($outsider)->count(),
            $this->getJson('/api/v1/ai/events?' . $this->range())->assertOk()->json('total')
        );
    }

    // --- occupancy -------------------------------------------------------------------------

    #[Test]
    public function the_occupancy_counts_hours_inside_the_day_window(): void
    {
        $room = Room::factory()->create(['name' => 'Probebuehne']);
        $this->eventIn($room, '2026-09-16 10:00:00', '2026-09-16 12:00:00');
        $this->actingAsToken(User::factory()->create());

        $payload = $this->getJson('/api/v1/ai/rooms/occupancy?' . $this->range() . '&room_ids[]=' . $room->id)
            ->assertOk()->json();

        $day = $payload['data'][0]['days'][0];

        $this->assertSame($room->id, $payload['data'][0]['room']['id']);
        $this->assertSame('2026-09-16', $day['date']);
        $this->assertEqualsWithDelta(2.0, $day['busy_hours'], 0.001);
        $this->assertEqualsWithDelta(12.0, $day['free_hours'], 0.001);
        $this->assertSame(1, $day['event_count']);
        $this->assertSame('08:00', $payload['day_window']['from']);
    }

    /** Parallele Termine duerfen die belegte Zeit nicht doppelt zaehlen. */
    #[Test]
    public function overlapping_events_count_once(): void
    {
        $room = Room::factory()->create();
        $this->eventIn($room, '2026-09-16 10:00:00', '2026-09-16 12:00:00');
        $this->eventIn($room, '2026-09-16 11:00:00', '2026-09-16 13:00:00');
        $this->actingAsToken(User::factory()->create());

        $day = $this->getJson('/api/v1/ai/rooms/occupancy?' . $this->range() . '&room_ids[]=' . $room->id)
            ->assertOk()->json('data.0.days.0');

        $this->assertEqualsWithDelta(3.0, $day['busy_hours'], 0.001);
        $this->assertSame(2, $day['event_count']);
    }

    // --- free slots ------------------------------------------------------------------------

    #[Test]
    public function free_slots_skip_the_booked_time(): void
    {
        $room = Room::factory()->create();
        $this->eventIn($room, '2026-09-16 10:00:00', '2026-09-16 14:00:00');
        $this->actingAsToken(User::factory()->create());

        $payload = $this->getJson(
            '/api/v1/ai/rooms/free-slots?' . $this->range() . '&room_ids[]=' . $room->id . '&min_hours=1'
        )->assertOk()->json();

        $this->assertCount(2, $payload['data']);
        $this->assertEqualsWithDelta(8.0, $payload['data'][0]['hours'], 0.001);
        $this->assertStringStartsWith('2026-09-16T14:00:00', $payload['data'][0]['start']);
        $this->assertSame($room->id, $payload['data'][0]['room_id']);
    }

    /**
     * Pflichtfall des Briefs: Ein Termin, dessen Inhalt die Person nicht sehen darf, blockiert
     * den Raum trotzdem. Belegung und Slots lesen deshalb **ohne** `visibleFor` — nach der
     * heutigen UI-Regel sieht die Person den Termin zwar ohnehin, aber die Rechnung darf sich
     * darauf nicht verlassen (B1 §5).
     */
    #[Test]
    public function an_event_of_a_foreign_project_still_blocks_the_room(): void
    {
        $room = Room::factory()->create();
        $foreignProject = Project::factory()->create();
        $this->eventIn($room, '2026-09-16 08:00:00', '2026-09-16 22:00:00', $foreignProject);

        $outsider = User::factory()->create();
        $this->actingAsToken($outsider);

        $this->assertSame(0, Project::query()->visibleFor($outsider)->count());

        $slots = $this->getJson(
            '/api/v1/ai/rooms/free-slots?' . $this->range() . '&room_ids[]=' . $room->id . '&min_hours=0.5'
        )->assertOk()->json('data');

        $this->assertSame([], $slots);

        $day = $this->getJson('/api/v1/ai/rooms/occupancy?' . $this->range() . '&room_ids[]=' . $room->id)
            ->assertOk()->json('data.0.days.0');

        $this->assertEqualsWithDelta(14.0, $day['busy_hours'], 0.001);
    }

    #[Test]
    public function free_slots_honour_weekdays_and_a_custom_day_window(): void
    {
        $room = Room::factory()->create();
        $this->actingAsToken(User::factory()->create());

        $range = 'from=' . $this->day->toDateString() . '&to=' . $this->day->addDays(6)->toDateString();
        $payload = $this->getJson(
            '/api/v1/ai/rooms/free-slots?' . $range . '&room_ids[]=' . $room->id
            . '&weekdays[]=6&weekdays[]=7&min_hours=2&day_window=18:00-23:00'
        )->assertOk()->json();

        $this->assertCount(2, $payload['data']);
        $this->assertEqualsWithDelta(5.0, $payload['data'][0]['hours'], 0.001);
        $this->assertSame('18:00', $payload['day_window']['from']);
        $this->assertEqualsWithDelta(2.0, $payload['min_hours'], 0.001);
    }

    #[Test]
    public function an_invalid_day_window_is_rejected(): void
    {
        $this->actingAsToken(User::factory()->create());

        $this->getJson('/api/v1/ai/rooms/free-slots?' . $this->range() . '&day_window=abends')
            ->assertStatus(422);
    }
}
