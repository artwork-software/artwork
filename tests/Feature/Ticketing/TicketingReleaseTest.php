<?php

namespace Tests\Feature\Ticketing;

use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Event\Models\SeriesEvents;
use Artwork\Modules\EventType\Models\EventType;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\Ticketing\Models\TicketingConnection;
use Artwork\Modules\Ticketing\Models\TicketingEventRelease;
use Artwork\Modules\Ticketing\Models\TicketingProduction;
use Artwork\Modules\Ticketing\Models\TicketingRoomLink;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\ActsAsRole;
use Tests\Feature\FeatureTestCase;

/** Plätze und Preise festhalten, Termine freigeben und zurückziehen — einzeln, mehrere, mit Serienregel. */
final class TicketingReleaseTest extends FeatureTestCase
{
    use ActsAsRole;

    private const TICKETS_URL = 'https://tickets.test';
    private const VENUE_ID = '0355e5ad-e8aa-405b-9550-038a557dc897';
    private const PRODUCTION_ID = '6f1d2c3b-4a5e-4f60-8a71-9b2c3d4e5f60';
    private const DATE_ID = '7a2e3d4c-5b6f-4a71-9b82-0c3d4e5f6a71';

    private Project $project;
    private Room $room;
    private EventType $type;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.tickets.url', self::TICKETS_URL);
        config()->set('services.tickets.provisioning_secret', str_repeat('s', 40));

        TicketingConnection::query()->create([
            'tickets_url' => self::TICKETS_URL,
            'organization_id' => 'org_1',
            'organization_slug' => 'theater',
            'dashboard_url' => self::TICKETS_URL . '/dashboard',
            'api_key' => 'tk_plain',
            'oauth_client_id' => 1,
            'connected_by_user_id' => null,
        ]);


        $this->actingAsAdmin();
        $this->project = Project::factory()->create(['name' => 'Hamlet']);
        $this->room = Room::factory()->create();
        TicketingRoomLink::query()->create(['room_id' => $this->room->id, 'venue_id' => self::VENUE_ID]);
        $this->type = EventType::factory()->create(['relevant_for_ticketing' => true]);
    }

    /** @param array<string, mixed> $stubs Antworten je URL, die die glücklichen Vorgaben ersetzen */
    private function fakeTickets(array $stubs = []): void
    {
        Http::fake($stubs + [
            self::TICKETS_URL . '/api/integration/v1/venues' => Http::response(['venues' => [[
                'id' => self::VENUE_ID,
                'name' => 'Großer Saal',
                'street' => '', 'postalCode' => '', 'city' => '', 'country' => 'DE',
                'zones' => [['key' => 'parkett', 'name' => 'Parkett', 'capacity' => 300, 'defaultPriceCents' => 2900]],
            ]]]),
            self::TICKETS_URL . '/api/integration/v1/reductions' => Http::response(['reductions' => []]),
            self::TICKETS_URL . '/api/integration/v1/productions' => Http::response(['id' => self::PRODUCTION_ID, 'status' => 'draft']),
            self::TICKETS_URL . '/api/integration/v1/productions/' . self::PRODUCTION_ID => Http::response([
                'id' => self::PRODUCTION_ID, 'status' => 'published', 'title' => 'Hamlet', 'description' => null,
                'shopUrl' => self::TICKETS_URL . '/theater/hamlet', 'heroImageUrl' => null, 'reductions' => [],
            ]),
            self::TICKETS_URL . '/api/integration/v1/dates/' . self::DATE_ID => Http::response([
                'id' => self::DATE_ID, 'status' => 'scheduled', 'capacity' => 300, 'sold' => 2, 'ticketCount' => 2, 'checkedInCount' => 0,
                'dashboardUrl' => self::TICKETS_URL . '/de/dashboard/events/x/dates/y',
                'tickets' => [['code' => 'ABCD1234', 'status' => 'valid', 'holderName' => 'Ada Lovelace', 'email' => 'ada@example.org', 'categoryName' => 'Parkett', 'checkedInAt' => null]],
            ]),
            self::TICKETS_URL . '/api/integration/v1/productions/*/publish' => Http::response(['id' => self::PRODUCTION_ID, 'status' => 'published']),
            self::TICKETS_URL . '/api/integration/v1/dates' => Http::response(['id' => self::DATE_ID, 'status' => 'scheduled']),
            self::TICKETS_URL . '/api/integration/v1/dates/*' => Http::response(['id' => self::DATE_ID, 'status' => 'cancelled', 'outcome' => 'deleted']),
        ]);
    }

    /** @return array{0: Event, 1: Event} */
    private function series(): array
    {
        $series = SeriesEvents::query()->create(['frequency_id' => 1, 'end_date' => '2027-01-31']);
        $make = fn (string $start): Event => Event::factory()->create([
            'project_id' => $this->project->id, 'event_type_id' => $this->type->id, 'room_id' => $this->room->id,
            'start_time' => $start, 'end_time' => substr($start, 0, 10) . ' 22:00:00',
            'is_series' => true, 'series_id' => $series->id,
        ]);

        return [$make('2027-01-10 19:30:00'), $make('2027-01-17 19:30:00')];
    }

    #[Test]
    public function a_draft_for_the_whole_series_reaches_every_date(): void
    {
        $this->fakeTickets();
        [$first, $second] = $this->series();

        $this->putJson(route('projects.tabs.ticketing.draft', $this->project), [
            'event_ids' => [$first->id, $second->id],
            'classes' => [['zone_key' => 'parkett', 'name' => 'Parkett', 'price_cents' => 3500, 'quota' => 250]],
        ])->assertOk()->assertJsonPath('events.1.release.classes.0.quota', 250);

        $this->assertSame(3500, TicketingEventRelease::query()->where('event_id', $second->id)->first()->classes[0]['price_cents']);
        $this->assertTrue($first->fresh()->is_series);
        Http::assertNotSent(fn (Request $request): bool => str_contains($request->url(), '/dates'));
    }

    #[Test]
    public function a_draft_for_a_single_date_takes_it_out_of_the_series(): void
    {
        $this->fakeTickets();
        [$first, $second] = $this->series();

        $this->putJson(route('projects.tabs.ticketing.draft', $this->project), [
            'event_ids' => [$first->id],
            'classes' => [
                ['zone_key' => 'parkett', 'name' => 'Parkett', 'price_cents' => 3900, 'quota' => 300],
                ['zone_key' => null, 'name' => 'Premium', 'price_cents' => 7900, 'quota' => 10],
            ],
        ])->assertOk();

        $this->assertFalse($first->fresh()->is_series);
        $this->assertNull($first->fresh()->series_id);
        $this->assertTrue($second->fresh()->is_series);
        $this->assertDatabaseMissing('ticketing_event_releases', ['event_id' => $second->id]);
    }

    #[Test]
    public function releasing_creates_the_production_pushes_the_date_and_publishes(): void
    {
        $this->fakeTickets();
        $user = auth()->user();
        $event = Event::factory()->create([
            'project_id' => $this->project->id, 'event_type_id' => $this->type->id, 'room_id' => $this->room->id,
            'start_time' => '2027-02-01 20:00:00', 'end_time' => '2027-02-01 22:00:00',
        ]);

        $this->postJson(route('projects.tabs.ticketing.release', $this->project), ['event_ids' => [$event->id]])
            ->assertOk()
            ->assertJsonPath('events.0.release.state', 'released');

        Http::assertSent(fn (Request $request): bool => $request->method() === 'PUT'
            && str_ends_with($request->url(), '/productions')
            && $request['externalRef'] === (string) $this->project->id
            && $request['title'] === 'Hamlet'
            && $request['venueId'] === self::VENUE_ID
            && $request['reductionTypeIds'] === null);
        Http::assertSent(fn (Request $request): bool => $request->method() === 'PUT'
            && str_ends_with($request->url(), '/dates')
            && $request['externalRef'] === (string) $event->id
            && $request['productionId'] === self::PRODUCTION_ID
            && $request['capacity'] === 300
            && $request['categories'][0] === ['zoneKey' => 'parkett', 'name' => 'Parkett', 'priceCents' => 2900, 'quota' => 300]);
        Http::assertSent(fn (Request $request): bool => $request->method() === 'POST'
            && str_ends_with($request->url(), '/productions/' . self::PRODUCTION_ID . '/publish'));

        $this->assertDatabaseHas('ticketing_productions', ['project_id' => $this->project->id, 'production_id' => self::PRODUCTION_ID]);
        $release = TicketingEventRelease::query()->where('event_id', $event->id)->firstOrFail();
        $this->assertSame(self::DATE_ID, $release->tickets_date_id);
        $this->assertSame($user->id, $release->released_by_user_id);
        // Die Vorgabe wird mit der Freigabe zum eigenen Stand des Termins.
        $this->assertSame(2900, $release->classes[0]['price_cents']);
    }

    #[Test]
    public function a_date_in_an_unsynced_room_cannot_be_released(): void
    {
        $this->fakeTickets();
        $event = Event::factory()->create([
            'project_id' => $this->project->id, 'event_type_id' => $this->type->id, 'room_id' => Room::factory()->create()->id,
        ]);

        $this->postJson(route('projects.tabs.ticketing.release', $this->project), ['event_ids' => [$event->id]])
            ->assertStatus(422);

        Http::assertNotSent(fn (Request $request): bool => str_contains($request->url(), '/productions'));
        $this->assertDatabaseMissing('ticketing_event_releases', ['event_id' => $event->id]);
    }

    #[Test]
    public function a_saved_draft_of_a_released_date_is_pushed_right_away(): void
    {
        $this->fakeTickets();
        $event = Event::factory()->create([
            'project_id' => $this->project->id, 'event_type_id' => $this->type->id, 'room_id' => $this->room->id,
            'start_time' => '2027-02-01 20:00:00', 'end_time' => '2027-02-01 22:00:00',
        ]);
        TicketingProduction::query()->create(['project_id' => $this->project->id, 'production_id' => self::PRODUCTION_ID]);
        TicketingEventRelease::query()->create([
            'event_id' => $event->id, 'state' => 'released', 'tickets_date_id' => self::DATE_ID,
            'classes' => [['zone_key' => 'parkett', 'name' => 'Parkett', 'price_cents' => 2900, 'quota' => 300]],
        ]);

        $this->putJson(route('projects.tabs.ticketing.draft', $this->project), [
            'event_ids' => [$event->id],
            'classes' => [
                ['zone_key' => 'parkett', 'name' => 'Parkett', 'price_cents' => 3100, 'quota' => 270],
                ['zone_key' => null, 'name' => 'Premium', 'price_cents' => 7900, 'quota' => 10],
            ],
        ])->assertOk();

        Http::assertSent(fn (Request $request): bool => $request->method() === 'PUT'
            && str_ends_with($request->url(), '/dates')
            && $request['capacity'] === 280
            && $request['categories'][0]['priceCents'] === 3100
            && $request['categories'][1] === ['zoneKey' => null, 'name' => 'Premium', 'priceCents' => 7900, 'quota' => 10]);
    }

    #[Test]
    public function withdrawing_deletes_the_date_in_tickets_and_keeps_the_draft(): void
    {
        $this->fakeTickets();
        $event = Event::factory()->create([
            'project_id' => $this->project->id, 'event_type_id' => $this->type->id, 'room_id' => $this->room->id,
        ]);
        TicketingEventRelease::query()->create([
            'event_id' => $event->id, 'state' => 'released', 'tickets_date_id' => self::DATE_ID,
            'classes' => [['zone_key' => 'parkett', 'name' => 'Parkett', 'price_cents' => 2900, 'quota' => 300]],
        ]);

        $this->deleteJson(route('projects.tabs.ticketing.withdraw', $this->project), ['event_ids' => [$event->id]])
            ->assertOk()
            ->assertJsonPath('events.0.release.state', 'draft')
            ->assertJsonPath('events.0.release.classes.0.quota', 300);

        Http::assertSent(fn (Request $request): bool => $request->method() === 'DELETE'
            && str_ends_with($request->url(), '/dates/' . self::DATE_ID));
        $this->assertNull(TicketingEventRelease::query()->where('event_id', $event->id)->value('tickets_date_id'));
    }

    #[Test]
    public function an_event_of_another_project_is_not_found(): void
    {
        $this->fakeTickets();
        $foreign = Event::factory()->create(['event_type_id' => $this->type->id]);

        $this->postJson(route('projects.tabs.ticketing.release', $this->project), ['event_ids' => [$foreign->id]])
            ->assertNotFound();
    }

    #[Test]
    public function the_production_draft_is_saved_and_pushed_once_linked(): void
    {
        $this->fakeTickets();
        $reduction = '3b9f1a2c-0d4e-4f5a-8b6c-7d8e9f0a1b2c';

        $this->postJson(route('projects.tabs.ticketing.production', $this->project), [
            'title' => 'Hamlet – Premiere',
            'description' => 'Shakespeare',
            'reduction_type_ids' => [$reduction],
        ])->assertOk()->assertJsonPath('production.title', 'Hamlet – Premiere');

        Http::assertNotSent(fn (Request $request): bool => str_ends_with($request->url(), '/productions'));

        TicketingProduction::query()->where('project_id', $this->project->id)->update(['production_id' => self::PRODUCTION_ID]);

        $this->post(route('projects.tabs.ticketing.production', $this->project), [
            'title' => 'Hamlet',
            'reduction_type_ids' => '[]',
        ], ['Accept' => 'application/json'])->assertOk()->assertJsonPath('production.reductionTypeIds', []);

        Http::assertSent(fn (Request $request): bool => $request->method() === 'PUT'
            && str_ends_with($request->url(), '/productions')
            && $request['title'] === 'Hamlet'
            && $request['reductionTypeIds'] === []
            && $request['venueId'] === null);
    }

    #[Test]
    public function releasing_uses_the_production_draft(): void
    {
        $this->fakeTickets();
        TicketingProduction::query()->create(['project_id' => $this->project->id, 'title' => 'Hamlet – Premiere']);
        $event = Event::factory()->create([
            'project_id' => $this->project->id, 'event_type_id' => $this->type->id, 'room_id' => $this->room->id,
            'start_time' => '2027-02-01 20:00:00', 'end_time' => '2027-02-01 22:00:00',
        ]);

        $this->postJson(route('projects.tabs.ticketing.release', $this->project), ['event_ids' => [$event->id]])->assertOk();

        Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/productions')
            && $request['title'] === 'Hamlet – Premiere');
        $this->assertSame(self::PRODUCTION_ID, TicketingProduction::query()->where('project_id', $this->project->id)->value('production_id'));
    }

    #[Test]
    public function the_sales_of_a_released_date_come_from_tickets(): void
    {
        $this->fakeTickets();
        $event = Event::factory()->create(['project_id' => $this->project->id, 'event_type_id' => $this->type->id]);
        $unreleased = Event::factory()->create(['project_id' => $this->project->id, 'event_type_id' => $this->type->id]);
        TicketingEventRelease::query()->create([
            'event_id' => $event->id, 'state' => 'released', 'tickets_date_id' => self::DATE_ID,
            'classes' => [['zone_key' => 'parkett', 'name' => 'Parkett', 'price_cents' => 2900, 'quota' => 300]],
        ]);

        $this->getJson(route('projects.tabs.ticketing.sales', [$this->project, $event]))
            ->assertOk()
            ->assertJsonPath('released', true)
            ->assertJsonPath('sold', 2)
            ->assertJsonPath('tickets.0.holderName', 'Ada Lovelace');

        $this->getJson(route('projects.tabs.ticketing.sales', [$this->project, $unreleased]))
            ->assertOk()
            ->assertJsonPath('released', false);
    }

    #[Test]
    public function a_date_in_the_past_cannot_be_released(): void
    {
        $this->fakeTickets();
        $event = Event::factory()->create([
            'project_id' => $this->project->id, 'event_type_id' => $this->type->id, 'room_id' => $this->room->id,
            'start_time' => now()->subDay(), 'end_time' => now()->subDay()->addHours(2),
        ]);

        $this->postJson(route('projects.tabs.ticketing.release', $this->project), ['event_ids' => [$event->id]])
            ->assertStatus(422)
            ->assertSeeText('has already taken place and cannot be sold any more.');

        Http::assertNotSent(fn (Request $request): bool => str_contains($request->url(), '/productions'));
    }

    #[Test]
    public function a_failed_publish_takes_the_date_back_out(): void
    {
        $this->fakeTickets([
            self::TICKETS_URL . '/api/integration/v1/productions/*/publish' => Http::response([
                'error' => ['code' => 'EVENT_NOT_READY', 'message' => 'The production cannot go on sale yet: no date lies in the future.'],
            ], 409),
        ]);
        $event = Event::factory()->create([
            'project_id' => $this->project->id, 'event_type_id' => $this->type->id, 'room_id' => $this->room->id,
            'start_time' => '2027-02-01 20:00:00', 'end_time' => '2027-02-01 22:00:00',
        ]);

        $this->postJson(route('projects.tabs.ticketing.release', $this->project), ['event_ids' => [$event->id]])
            ->assertStatus(422)
            ->assertJsonFragment(['message' => 'The production cannot go on sale yet: no date lies in the future.']);

        Http::assertSent(fn (Request $request): bool => $request->method() === 'DELETE'
            && str_ends_with($request->url(), '/dates/' . self::DATE_ID));
        $this->assertDatabaseMissing('ticketing_event_releases', ['event_id' => $event->id, 'state' => 'released']);
    }

    #[Test]
    public function a_released_date_follows_its_event_when_it_moves_and_leaves_when_it_is_deleted(): void
    {
        $this->fakeTickets();
        $event = Event::factory()->create([
            'project_id' => $this->project->id, 'event_type_id' => $this->type->id, 'room_id' => $this->room->id,
            'start_time' => '2027-02-01 20:00:00', 'end_time' => '2027-02-01 22:00:00',
        ]);
        $this->postJson(route('projects.tabs.ticketing.release', $this->project), ['event_ids' => [$event->id]])->assertOk();

        $event->update(['start_time' => '2027-02-02 20:00:00']);

        $this->assertCount(2, Http::recorded(fn (Request $request): bool => $request->method() === 'PUT'
            && str_ends_with($request->url(), '/dates')));
        $this->assertStringContainsString('2027-02-02T20:00', Http::recorded()->last()[0]->data()['startsAt']);

        $event->delete();

        Http::assertSent(fn (Request $request): bool => $request->method() === 'DELETE'
            && str_ends_with($request->url(), '/dates/' . self::DATE_ID));
    }

    #[Test]
    public function a_draft_for_part_of_a_series_takes_only_those_dates_out(): void
    {
        $this->fakeTickets();
        [$first, $second] = $this->series();
        $third = Event::factory()->create([
            'project_id' => $this->project->id, 'event_type_id' => $this->type->id, 'room_id' => $this->room->id,
            'start_time' => '2027-01-24 19:30:00', 'end_time' => '2027-01-24 22:00:00',
            'is_series' => true, 'series_id' => $first->series_id,
        ]);

        $this->putJson(route('projects.tabs.ticketing.draft', $this->project), [
            'event_ids' => [$first->id, $third->id],
            'classes' => [['zone_key' => 'parkett', 'name' => 'Parkett', 'price_cents' => 3400, 'quota' => 300]],
        ])->assertOk();

        $this->assertFalse($first->fresh()->is_series);
        $this->assertFalse($third->fresh()->is_series);
        $this->assertTrue($second->fresh()->is_series);
        $this->assertSame(3400, TicketingEventRelease::query()->where('event_id', $third->id)->first()->classes[0]['price_cents']);
        $this->assertDatabaseMissing('ticketing_event_releases', ['event_id' => $second->id]);
    }

    #[Test]
    public function several_dates_are_released_together_and_published_once(): void
    {
        $this->fakeTickets();
        $make = fn (string $start): Event => Event::factory()->create([
            'project_id' => $this->project->id, 'event_type_id' => $this->type->id, 'room_id' => $this->room->id,
            'start_time' => $start, 'end_time' => substr($start, 0, 10) . ' 22:00:00',
        ]);
        $first = $make('2027-03-01 20:00:00');
        $second = $make('2027-03-02 20:00:00');
        $released = $make('2027-03-03 20:00:00');
        TicketingProduction::query()->create(['project_id' => $this->project->id, 'production_id' => self::PRODUCTION_ID]);
        TicketingEventRelease::query()->create([
            'event_id' => $released->id, 'state' => 'released', 'tickets_date_id' => self::DATE_ID,
            'classes' => [['zone_key' => 'parkett', 'name' => 'Parkett', 'price_cents' => 2900, 'quota' => 300]],
        ]);

        $this->postJson(route('projects.tabs.ticketing.release', $this->project), ['event_ids' => [$first->id, $second->id, $released->id]])
            ->assertOk()
            ->assertJsonPath('events.0.release.state', 'released')
            ->assertJsonPath('events.1.release.state', 'released');

        $this->assertCount(2, Http::recorded(fn (Request $request): bool => $request->method() === 'PUT' && str_ends_with($request->url(), '/dates')));
        $this->assertCount(1, Http::recorded(fn (Request $request): bool => str_ends_with($request->url(), '/publish')));
        Http::assertSent(fn (Request $request): bool => $request->method() === 'PUT'
            && str_ends_with($request->url(), '/dates') && $request['externalRef'] === (string) $first->id);
        Http::assertSent(fn (Request $request): bool => $request->method() === 'PUT'
            && str_ends_with($request->url(), '/dates') && $request['externalRef'] === (string) $second->id);
        Http::assertNotSent(fn (Request $request): bool => str_ends_with($request->url(), '/dates')
            && $request['externalRef'] === (string) $released->id);
    }

    #[Test]
    public function a_list_with_a_foreign_date_changes_nothing(): void
    {
        $this->fakeTickets();
        $own = Event::factory()->create(['project_id' => $this->project->id, 'event_type_id' => $this->type->id, 'room_id' => $this->room->id]);
        $foreign = Event::factory()->create(['event_type_id' => $this->type->id]);

        $this->putJson(route('projects.tabs.ticketing.draft', $this->project), [
            'event_ids' => [$own->id, $foreign->id],
            'classes' => [['zone_key' => 'parkett', 'name' => 'Parkett', 'price_cents' => 3400, 'quota' => 300]],
        ])->assertNotFound();

        $this->assertDatabaseMissing('ticketing_event_releases', ['event_id' => $own->id]);
    }
}
