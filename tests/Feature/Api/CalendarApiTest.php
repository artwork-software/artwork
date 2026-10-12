<?php

namespace Tests\Feature\Api;

use App\Settings\EventSettings;
use Artwork\Modules\Area\Models\Area;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Event\Models\EventStatus;
use Artwork\Modules\Event\Models\SeriesEvents;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\Room\Models\RoomCategory;
use Artwork\Modules\User\Models\User;
use Carbon\Carbon;
use Laravel\Passport\Passport;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Raumbelegungs-Schnittstelle der Maschinen-API: GET /api/v1/rooms und GET /api/v1/events
 * (Scope calendar:read). Die Feldnamen sind ein Vertrag mit externen Systemen.
 */
final class CalendarApiTest extends FeatureTestCase
{
    private Area $area;

    private Room $blueRoom;

    private Room $pinkRoom;

    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        if (!file_exists(Passport::keyPath('oauth-private.key'))) {
            $this->artisan('passport:keys');
        }

        Carbon::setTestNow(Carbon::parse('2026-10-12 09:15:00'));

        $this->area = Area::factory()->create(['name' => 'Gästewohnung']);
        $this->blueRoom = Room::factory()->create([
            'name' => 'Gästewohnung - Zimmer Blau',
            'area_id' => $this->area->id,
            'capacity' => 2,
        ]);
        $this->pinkRoom = Room::factory()->create([
            'name' => 'Gästewohnung - Zimmer Rosa',
            'area_id' => $this->area->id,
        ]);
        $this->project = Project::factory()->create(['name' => 'Gastspiel Nord']);

        Passport::actingAs(User::factory()->create(), ['calendar:read']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    #[Test]
    public function requests_without_token_get_a_json_401_even_without_accept_header(): void
    {
        $this->app['auth']->forgetGuards();

        $this->get('/api/v1/events?from=2027-01-01&to=2027-12-31')
            ->assertUnauthorized()
            ->assertJsonStructure(['message']);

        $this->get('/api/v1/rooms')->assertUnauthorized();
        $this->get('/api/v1/event-statuses')->assertUnauthorized();
    }

    #[Test]
    public function tokens_without_calendar_scope_are_rejected(): void
    {
        Passport::actingAs(User::factory()->create(), ['inventory:read']);

        $this->getJson('/api/v1/events?from=2027-01-01&to=2027-12-31')->assertForbidden();
        $this->getJson('/api/v1/rooms')->assertForbidden();
        $this->getJson('/api/v1/event-statuses')->assertForbidden();
    }

    #[Test]
    public function calendar_scope_does_not_grant_inventory_access(): void
    {
        $this->getJson('/api/v1/inventory')->assertForbidden();
    }

    #[Test]
    public function rooms_are_listed_with_stable_ids_area_and_categories(): void
    {
        $category = RoomCategory::create(['name' => 'Wohnung']);
        $this->blueRoom->categories()->attach($category->id);
        $trashedRoom = Room::factory()->create(['area_id' => $this->area->id]);
        $trashedRoom->delete();

        $response = $this->getJson('/api/v1/rooms?areas=' . $this->area->id)->assertOk();

        $this->assertSame(
            [$this->blueRoom->id, $this->pinkRoom->id],
            array_column($response->json('data'), 'id')
        );
        $response->assertJsonPath('data.0', [
            'id' => $this->blueRoom->id,
            'name' => 'Gästewohnung - Zimmer Blau',
            'area_id' => $this->area->id,
            'area_name' => 'Gästewohnung',
            'categories' => [['id' => $category->id, 'name' => 'Wohnung']],
            'capacity' => 2,
            'color' => $this->blueRoom->fresh()->getEffectiveColor(),
            'temporary' => false,
            'temporary_from' => null,
            'temporary_until' => null,
            'updated_at' => $this->blueRoom->fresh()->updated_at->toIso8601String(),
        ]);
        $response->assertJsonPath('generated_at', '2026-10-12T09:15:00+02:00');
    }

    #[Test]
    public function rooms_without_area_filter_include_all_rooms(): void
    {
        $otherRoom = Room::factory()->create();

        $ids = array_column($this->getJson('/api/v1/rooms')->assertOk()->json('data'), 'id');

        $this->assertContains($this->blueRoom->id, $ids);
        $this->assertContains($otherRoom->id, $ids);
    }

    #[Test]
    public function events_contain_the_contract_fields_without_personal_data(): void
    {
        $status = EventStatus::factory()->create(['name' => 'Optioniert']);
        $this->setStatusEnabled(true);
        $event = $this->createEvent($this->blueRoom, '2027-03-01 15:00:00', '2027-03-05 10:00:00', [
            'event_status_id' => $status->id,
            'description' => 'Gäste: Max Muster, +41 79 000 00 00',
        ]);

        $response = $this->getJson($this->eventsUrl('2027-03-01', '2027-03-31'))->assertOk();

        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0', [
            'id' => $event->id,
            'title' => 'Gastspiel Nord',
            'project_id' => $this->project->id,
            'project_name' => 'Gastspiel Nord',
            'event_type' => ['id' => $event->event_type->id, 'name' => $event->event_type->name],
            'room_id' => $this->blueRoom->id,
            'room_name' => 'Gästewohnung - Zimmer Blau',
            'area_id' => $this->area->id,
            'area_name' => 'Gästewohnung',
            'start' => '2027-03-01T15:00:00+01:00',
            'end' => '2027-03-05T10:00:00+01:00',
            'all_day' => false,
            'start_date' => '2027-03-01',
            'end_date' => '2027-03-05',
            'status' => ['id' => $status->id, 'name' => 'Optioniert'],
            'room_request_pending' => false,
            'series_id' => null,
            'updated_at' => $event->fresh()->updated_at->toIso8601String(),
            'deleted_at' => null,
        ]);
        $this->assertStringNotContainsString('Max Muster', $response->getContent());
        $response->assertJsonPath('generated_at', '2026-10-12T09:15:00+02:00');
    }

    #[Test]
    public function status_is_null_when_event_status_is_disabled(): void
    {
        $status = EventStatus::factory()->create();
        $this->setStatusEnabled(false);
        $this->createEvent($this->blueRoom, '2027-03-01 10:00:00', '2027-03-01 12:00:00', [
            'event_status_id' => $status->id,
        ]);

        $this->getJson($this->eventsUrl('2027-03-01', '2027-03-01'))
            ->assertOk()
            ->assertJsonPath('data.0.status', null);
    }

    #[Test]
    public function all_day_events_report_the_last_occupied_day_as_end_date(): void
    {
        $this->createEvent($this->blueRoom, '2027-01-11 00:00:00', '2027-01-16 23:59:59', ['allDay' => true]);
        // Ende exakt um Mitternacht belegt den Folgetag nicht.
        $this->createEvent($this->pinkRoom, '2027-01-20 20:00:00', '2027-01-21 00:00:00');

        $data = $this->getJson($this->eventsUrl('2027-01-01', '2027-01-31'))->assertOk()->json('data');

        $this->assertSame(['2027-01-11', '2027-01-16', true], [
            $data[0]['start_date'],
            $data[0]['end_date'],
            $data[0]['all_day'],
        ]);
        $this->assertSame(['2027-01-20', '2027-01-20'], [$data[1]['start_date'], $data[1]['end_date']]);
    }

    #[Test]
    public function events_touching_the_range_are_delivered_with_their_real_dates(): void
    {
        $acrossNewYear = $this->createEvent($this->blueRoom, '2026-12-28 15:00:00', '2027-01-03 10:00:00');
        $insideRange = $this->createEvent($this->blueRoom, '2027-01-10 10:00:00', '2027-01-10 12:00:00');
        $endsOnLastDay = $this->createEvent($this->blueRoom, '2027-01-31 22:00:00', '2027-02-01 02:00:00');
        $this->createEvent($this->blueRoom, '2026-12-20 10:00:00', '2026-12-31 23:00:00');
        // Endet exakt zu Beginn des Zeitraums: berührt ihn nicht.
        $this->createEvent($this->blueRoom, '2026-12-31 20:00:00', '2027-01-01 00:00:00');
        $this->createEvent($this->blueRoom, '2027-02-01 00:00:00', '2027-02-01 10:00:00');

        $data = $this->getJson($this->eventsUrl('2027-01-01', '2027-01-31'))->assertOk()->json('data');

        $this->assertSame([$acrossNewYear->id, $insideRange->id, $endsOnLastDay->id], array_column($data, 'id'));
        $this->assertSame('2026-12-28T15:00:00+01:00', $data[0]['start']);
    }

    #[Test]
    public function planning_events_events_without_room_and_events_in_trashed_rooms_are_excluded(): void
    {
        $regular = $this->createEvent($this->blueRoom, '2027-03-01 10:00:00', '2027-03-01 12:00:00');
        $this->createEvent($this->blueRoom, '2027-03-02 10:00:00', '2027-03-02 12:00:00', ['is_planning' => true]);
        $this->createEvent($this->pinkRoom, '2027-03-03 10:00:00', '2027-03-03 12:00:00')->delete();
        $trashedRoom = Room::factory()->create(['area_id' => $this->area->id]);
        $this->createEvent($trashedRoom, '2027-03-04 10:00:00', '2027-03-04 12:00:00');
        $trashedRoom->delete();
        Event::factory()->create([
            'room_id' => null,
            'project_id' => $this->project->id,
            'start_time' => '2027-03-05 10:00:00',
            'end_time' => '2027-03-05 12:00:00',
        ]);

        $data = $this->getJson('/api/v1/events?from=2027-03-01&to=2027-03-31&areas=' . $this->area->id)
            ->assertOk()
            ->json('data');

        $this->assertSame([$regular->id], array_column($data, 'id'));
    }

    #[Test]
    public function open_room_requests_are_flagged(): void
    {
        $this->createEvent($this->blueRoom, '2027-03-01 10:00:00', '2027-03-01 12:00:00', [
            'occupancy_option' => true,
            'accepted' => false,
        ]);

        $this->getJson($this->eventsUrl('2027-03-01', '2027-03-01'))
            ->assertOk()
            ->assertJsonPath('data.0.room_request_pending', true);
    }

    #[Test]
    public function title_falls_back_to_event_name_and_then_event_type(): void
    {
        $named = $this->createEvent($this->blueRoom, '2027-03-01 10:00:00', '2027-03-01 12:00:00', [
            'project_id' => null,
            'eventName' => 'Hausreinigung',
        ]);
        $unnamed = $this->createEvent($this->blueRoom, '2027-03-02 10:00:00', '2027-03-02 12:00:00', [
            'project_id' => null,
            'eventName' => null,
        ]);

        $data = $this->getJson($this->eventsUrl('2027-03-01', '2027-03-31'))->assertOk()->json('data');

        $this->assertSame(['Hausreinigung', null], [$data[0]['title'], $data[0]['project_id']]);
        $this->assertSame($unnamed->event_type->name, $data[1]['title']);
        $this->assertSame($named->id, $data[0]['id']);
    }

    #[Test]
    public function rooms_and_areas_filters_combine_as_union(): void
    {
        $otherArea = Area::factory()->create();
        $studio = Room::factory()->create(['area_id' => $otherArea->id]);
        $silo = Room::factory()->create(['area_id' => $otherArea->id]);
        $inBlue = $this->createEvent($this->blueRoom, '2027-03-01 10:00:00', '2027-03-01 12:00:00');
        $inPink = $this->createEvent($this->pinkRoom, '2027-03-02 10:00:00', '2027-03-02 12:00:00');
        $inStudio = $this->createEvent($studio, '2027-03-03 10:00:00', '2027-03-03 12:00:00');
        $this->createEvent($silo, '2027-03-04 10:00:00', '2027-03-04 12:00:00');

        $onlyBlue = $this->getJson('/api/v1/events?from=2027-03-01&to=2027-03-31&rooms=' . $this->blueRoom->id)
            ->assertOk()->json('data');
        $this->assertSame([$inBlue->id], array_column($onlyBlue, 'id'));

        $union = $this->getJson(sprintf(
            '/api/v1/events?from=2027-03-01&to=2027-03-31&rooms=%d&areas=%d',
            $studio->id,
            $this->area->id
        ))->assertOk()->json('data');
        $this->assertSame([$inBlue->id, $inPink->id, $inStudio->id], array_column($union, 'id'));

        $arraySyntax = $this->getJson(sprintf(
            '/api/v1/events?from=2027-03-01&to=2027-03-31&rooms[]=%d&rooms[]=%d',
            $this->blueRoom->id,
            $this->pinkRoom->id
        ))->assertOk()->json('data');
        $this->assertSame([$inBlue->id, $inPink->id], array_column($arraySyntax, 'id'));
    }

    #[Test]
    public function updated_since_returns_changed_and_deleted_events(): void
    {
        $unchanged = $this->createEvent($this->blueRoom, '2027-03-01 10:00:00', '2027-03-01 12:00:00');
        $deletedBefore = $this->createEvent($this->blueRoom, '2027-03-02 10:00:00', '2027-03-02 12:00:00');
        $deletedBefore->delete();

        Carbon::setTestNow(Carbon::parse('2026-10-12 10:00:00'));
        $changed = $this->createEvent($this->blueRoom, '2027-03-03 10:00:00', '2027-03-03 12:00:00');
        $deletedAfter = $this->createEvent($this->blueRoom, '2027-03-04 10:00:00', '2027-03-04 12:00:00');
        $deletedAfter->delete();

        // „+“ unkodiert: kommt als Leerzeichen an und wird repariert.
        $data = $this->getJson(
            $this->eventsUrl('2027-03-01', '2027-03-31') . '&updated_since=2026-10-12T09:30:00+02:00'
        )->assertOk()->json('data');

        $this->assertSame([$changed->id, $deletedAfter->id], array_column($data, 'id'));
        $this->assertNull($data[0]['deleted_at']);
        $this->assertSame('2026-10-12T10:00:00+02:00', $data[1]['deleted_at']);
        $this->assertNotContains($unchanged->id, array_column($data, 'id'));

        $utc = $this->getJson(
            $this->eventsUrl('2027-03-01', '2027-03-31') . '&updated_since=' . urlencode('2026-10-12T07:30:00Z')
        )->assertOk()->json('data');
        $this->assertSame([$changed->id, $deletedAfter->id], array_column($utc, 'id'));
    }

    #[Test]
    public function updated_since_includes_events_whose_project_was_renamed(): void
    {
        $event = $this->createEvent($this->blueRoom, '2027-03-01 10:00:00', '2027-03-01 12:00:00');

        Carbon::setTestNow(Carbon::parse('2026-10-12 10:00:00'));
        $this->project->update(['name' => 'Gastspiel Nord (neu)']);

        $data = $this->getJson(
            $this->eventsUrl('2027-03-01', '2027-03-31') . '&updated_since=' . urlencode('2026-10-12T09:30:00+02:00')
        )->assertOk()->json('data');

        $this->assertSame([$event->id], array_column($data, 'id'));
        $this->assertSame('Gastspiel Nord (neu)', $data[0]['project_name']);
    }

    #[Test]
    public function results_are_paginated_with_next_link(): void
    {
        $first = $this->createEvent($this->blueRoom, '2027-03-01 10:00:00', '2027-03-01 12:00:00');
        $second = $this->createEvent($this->blueRoom, '2027-03-02 10:00:00', '2027-03-02 12:00:00');
        $third = $this->createEvent($this->blueRoom, '2027-03-03 10:00:00', '2027-03-03 12:00:00');

        $page1 = $this->getJson($this->eventsUrl('2027-03-01', '2027-03-31') . '&per_page=2')->assertOk();

        $this->assertSame([$first->id, $second->id], array_column($page1->json('data'), 'id'));
        $page1->assertJsonPath('meta.total', 3)
            ->assertJsonPath('meta.last_page', 2)
            ->assertJsonPath('meta.per_page', 2)
            ->assertJsonMissingPath('meta.links');
        $this->assertStringContainsString('page=2', $page1->json('links.next'));
        $this->assertStringContainsString('rooms=' . $this->blueRoom->id, $page1->json('links.next'));

        $page2 = $this->getJson($page1->json('links.next'))->assertOk();
        $this->assertSame([$third->id], array_column($page2->json('data'), 'id'));
        $page2->assertJsonPath('links.next', null);
    }

    #[Test]
    public function empty_range_returns_200_with_empty_list(): void
    {
        $this->getJson($this->eventsUrl('2030-01-01', '2030-12-31'))
            ->assertOk()
            ->assertExactJson([
                'data' => [],
                'links' => [
                    'first' => url('/api/v1/events') . '?' . http_build_query([
                        'from' => '2030-01-01',
                        'to' => '2030-12-31',
                        'rooms' => (string) $this->blueRoom->id . ',' . $this->pinkRoom->id,
                        'page' => 1,
                    ]),
                    'last' => url('/api/v1/events') . '?' . http_build_query([
                        'from' => '2030-01-01',
                        'to' => '2030-12-31',
                        'rooms' => (string) $this->blueRoom->id . ',' . $this->pinkRoom->id,
                        'page' => 1,
                    ]),
                    'prev' => null,
                    'next' => null,
                ],
                'meta' => [
                    'current_page' => 1,
                    'from' => null,
                    'last_page' => 1,
                    'path' => url('/api/v1/events'),
                    'per_page' => 500,
                    'to' => null,
                    'total' => 0,
                ],
                'generated_at' => '2026-10-12T09:15:00+02:00',
            ]);
    }

    #[Test]
    public function invalid_parameters_return_400_with_message(): void
    {
        $this->get('/api/v1/events?to=2027-12-31')
            ->assertStatus(400)
            ->assertJsonStructure(['message', 'errors' => ['from']]);

        $this->getJson('/api/v1/events?from=2027-12-31&to=2027-01-01')
            ->assertStatus(400)
            ->assertJsonValidationErrors(['to']);

        $this->getJson('/api/v1/events?from=2027-01-01&to=2030-01-01')
            ->assertStatus(400)
            ->assertJsonValidationErrors(['to']);

        $this->getJson('/api/v1/events?from=01.01.2027&to=2027-12-31')
            ->assertStatus(400)
            ->assertJsonValidationErrors(['from']);

        $this->getJson('/api/v1/events?from=2027-01-01&to=2027-12-31&rooms=999999999')
            ->assertStatus(400)
            ->assertJsonValidationErrors(['rooms.0']);

        $this->getJson('/api/v1/events?from=2027-01-01&to=2027-12-31&per_page=1001')
            ->assertStatus(400)
            ->assertJsonValidationErrors(['per_page']);

        $this->getJson('/api/v1/events?from=2027-01-01&to=2027-12-31&updated_since=gestern')
            ->assertStatus(400)
            ->assertJsonValidationErrors(['updated_since']);

        $this->getJson('/api/v1/rooms?areas=abc')
            ->assertStatus(400)
            ->assertJsonValidationErrors(['areas.0']);
    }

    #[Test]
    public function series_events_share_their_series_id(): void
    {
        $series = SeriesEvents::create([
            'frequency_id' => 1,
            'end_date' => '2027-03-03',
        ]);
        $first = $this->createEvent($this->blueRoom, '2027-03-01 10:00:00', '2027-03-01 18:00:00', [
            'is_series' => true,
            'series_id' => $series->id,
        ]);
        $second = $this->createEvent($this->blueRoom, '2027-03-02 10:00:00', '2027-03-02 18:00:00', [
            'is_series' => true,
            'series_id' => $series->id,
        ]);
        $single = $this->createEvent($this->blueRoom, '2027-03-03 10:00:00', '2027-03-03 18:00:00');

        $data = $this->getJson($this->eventsUrl('2027-03-01', '2027-03-31'))->assertOk()->json('data');

        $this->assertSame([$first->id, $second->id, $single->id], array_column($data, 'id'));
        $this->assertSame([$series->id, $series->id, null], array_column($data, 'series_id'));
    }

    #[Test]
    public function event_statuses_are_listed_in_order_with_enabled_flag(): void
    {
        $this->setStatusEnabled(true);
        $later = EventStatus::factory()->create(['name' => 'Abgesagt', 'order' => 9001, 'color' => '#ff0000']);
        $earlier = EventStatus::factory()->create(['name' => 'Optioniert', 'order' => 9000, 'color' => '#00ff00']);

        $response = $this->getJson('/api/v1/event-statuses')->assertOk();

        $statuses = collect($response->json('data'));
        $ids = $statuses->pluck('id')->all();
        $this->assertLessThan(array_search($later->id, $ids, true), array_search($earlier->id, $ids, true));
        $this->assertSame([
            'id' => $later->id,
            'name' => 'Abgesagt',
            'color' => '#ff0000',
            'order' => 9001,
            'default' => false,
        ], $statuses->firstWhere('id', $later->id));
        $response->assertJsonPath('status_enabled', true)
            ->assertJsonPath('generated_at', '2026-10-12T09:15:00+02:00');

        $this->setStatusEnabled(false);
        $this->getJson('/api/v1/event-statuses')->assertOk()->assertJsonPath('status_enabled', false);
    }

    #[Test]
    public function exactly_three_years_are_allowed(): void
    {
        $this->getJson($this->eventsUrl('2027-01-01', '2029-12-31'))->assertOk();
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function createEvent(Room $room, string $start, string $end, array $attributes = []): Event
    {
        return Event::factory()->create(array_merge([
            'room_id' => $room->id,
            'project_id' => $this->project->id,
            'start_time' => $start,
            'end_time' => $end,
            'allDay' => false,
            'occupancy_option' => false,
            'accepted' => true,
            'is_planning' => false,
        ], $attributes));
    }

    private function eventsUrl(string $from, string $to): string
    {
        return sprintf(
            '/api/v1/events?from=%s&to=%s&rooms=%d,%d',
            $from,
            $to,
            $this->blueRoom->id,
            $this->pinkRoom->id
        );
    }

    private function setStatusEnabled(bool $enabled): void
    {
        $settings = app(EventSettings::class);
        $settings->enable_status = $enabled;
        $settings->save();
    }
}
