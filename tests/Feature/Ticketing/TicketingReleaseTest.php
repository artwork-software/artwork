<?php

namespace Tests\Feature\Ticketing;

use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Event\Models\SeriesEvents;
use Artwork\Modules\EventType\Models\EventType;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\Ticketing\Exceptions\TicketingLockedException;
use Artwork\Modules\Ticketing\Jobs\SyncTicketingEventJob;
use Artwork\Modules\Ticketing\Models\TicketingConnection;
use Artwork\Modules\Ticketing\Models\TicketingEventRelease;
use Artwork\Modules\Ticketing\Models\TicketingProduction;
use Artwork\Modules\Ticketing\Models\TicketingProductionImage;
use Artwork\Modules\Ticketing\Models\TicketingRoomLink;
use Artwork\Modules\Ticketing\Services\TicketingLock;
use Illuminate\Http\Client\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
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
            self::TICKETS_URL . '/api/integration/v1/house/billing' => Http::response(['profile' => [], 'legalComplete' => true, 'shopLegalComplete' => true, 'payoutAccount' => 'verified', 'platformTerms' => ['accepted' => true]]),
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
                'revenueCents' => 5800,
                'tickets' => [['code' => 'ABCD1234', 'status' => 'valid', 'holderName' => 'Ada Lovelace', 'email' => 'ada@example.org', 'categoryName' => 'Parkett', 'reductionName' => null, 'priceCents' => 2900, 'checkedInAt' => null]],
            ]),
            self::TICKETS_URL . '/api/integration/v1/dates?ids=*' => Http::response([
                ['id' => self::DATE_ID, 'status' => 'scheduled', 'capacity' => 300, 'sold' => 84],
            ]),
            self::TICKETS_URL . '/api/integration/v1/login-links' => Http::response(['url' => self::TICKETS_URL . '/api/auth/core-login/verify?token=t0k3n']),
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
            'description' => null,
            'reductions' => [],
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
            'description' => null,
            'reductions' => [],
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
            'description' => null,
            'reductions' => [],
        ])->assertOk();

        Http::assertSent(fn (Request $request): bool => $request->method() === 'PUT'
            && str_ends_with($request->url(), '/dates')
            && $request['capacity'] === 280
            && $request['categories'][0]['priceCents'] === 3100
            && $request['categories'][1] === ['zoneKey' => null, 'name' => 'Premium', 'priceCents' => 7900, 'quota' => 10]);
    }

    #[Test]
    public function a_date_keeps_its_own_text_and_reductions_and_sends_them_to_the_shop(): void
    {
        $this->fakeTickets();
        $pupils = '1b2c3d4e-5f60-4a71-8b82-9c3d4e5f6a71';
        $members = '2c3d4e5f-6a71-4b82-9c93-0d4e5f6a7b82';
        $event = $this->releasedEvent();

        $this->putJson(route('projects.tabs.ticketing.draft', $this->project), [
            'event_ids' => [$event->id],
            'classes' => [['zone_key' => 'parkett', 'name' => 'Parkett', 'price_cents' => 2900, 'quota' => 300]],
            'description' => "## Premiere\nMit anschließender Feier.",
            'reductions' => [['id' => $pupils, 'offered' => false], ['id' => $members, 'offered' => true]],
        ])->assertOk()
            ->assertJsonPath('events.0.release.description', "## Premiere\nMit anschließender Feier.")
            ->assertJsonPath('events.0.release.reductions.1', ['id' => $members, 'offered' => true]);

        Http::assertSent(fn (Request $request): bool => $request->method() === 'PUT'
            && str_ends_with($request->url(), '/dates')
            && $request['description'] === "## Premiere\nMit anschließender Feier."
            && $request['reductions'] === [
                ['reductionTypeId' => $pupils, 'offered' => false],
                ['reductionTypeId' => $members, 'offered' => true],
            ]);
    }

    #[Test]
    public function a_date_without_own_text_follows_the_production_in_the_shop(): void
    {
        $this->fakeTickets();
        $event = Event::factory()->create([
            'project_id' => $this->project->id, 'event_type_id' => $this->type->id, 'room_id' => $this->room->id,
            'start_time' => '2027-02-01 20:00:00', 'end_time' => '2027-02-01 22:00:00',
        ]);

        $this->postJson(route('projects.tabs.ticketing.release', $this->project), ['event_ids' => [$event->id]])->assertOk();

        Http::assertSent(fn (Request $request): bool => $request->method() === 'PUT'
            && str_ends_with($request->url(), '/dates')
            && $request['description'] === null
            && $request['reductions'] === []);
    }

    #[Test]
    public function a_reduction_is_named_by_its_tickets_id(): void
    {
        $this->fakeTickets();
        $event = Event::factory()->create(['project_id' => $this->project->id, 'event_type_id' => $this->type->id, 'room_id' => $this->room->id]);

        $this->putJson(route('projects.tabs.ticketing.draft', $this->project), [
            'event_ids' => [$event->id],
            'classes' => [['zone_key' => 'parkett', 'name' => 'Parkett', 'price_cents' => 2900, 'quota' => 300]],
            'description' => null,
            'reductions' => [['id' => 'students', 'offered' => false]],
        ])->assertUnprocessable()->assertJsonValidationErrors('reductions.0.id');
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
    public function further_pictures_are_pushed_once_and_removed_in_tickets_too(): void
    {
        Storage::fake();
        $imageId = '8b3f4e5d-6c7a-4b82-8c93-1d4e5f6a7b82';
        $this->fakeTickets([
            self::TICKETS_URL . '/api/integration/v1/productions/*/images/*' => Http::response(['removed' => true]),
            self::TICKETS_URL . '/api/integration/v1/productions/*/images' => Http::response(['id' => $imageId, 'url' => 'https://cdn.test/a.jpg', 'alt' => null]),
        ]);
        TicketingProduction::query()->create(['project_id' => $this->project->id, 'production_id' => self::PRODUCTION_ID]);

        $this->post(route('projects.tabs.ticketing.production', $this->project), [
            'images' => [UploadedFile::fake()->image('foyer.jpg'), UploadedFile::fake()->image('probe.jpg')],
        ], ['Accept' => 'application/json'])->assertOk()->assertJsonCount(2, 'production.images');

        $this->assertSame(2, $this->imagesPosted());
        $this->assertSame(2, TicketingProductionImage::query()->where('remote_id', $imageId)->count());

        // Ein Speichern ohne neue Bilder schickt keines noch einmal.
        $this->post(route('projects.tabs.ticketing.production', $this->project), ['title' => 'Hamlet'], ['Accept' => 'application/json'])->assertOk();
        $this->assertSame(2, $this->imagesPosted());

        $first = TicketingProductionImage::query()->orderBy('id')->firstOrFail();
        $this->post(route('projects.tabs.ticketing.production', $this->project), [
            'remove_image_ids' => [$first->id],
        ], ['Accept' => 'application/json'])->assertOk()->assertJsonCount(1, 'production.images');

        Http::assertSent(fn (Request $request): bool => $request->method() === 'DELETE'
            && str_ends_with($request->url(), '/productions/' . self::PRODUCTION_ID . '/images/' . $imageId));
        Storage::assertMissing($first->storagePath());
    }

    #[Test]
    public function further_pictures_stop_at_the_limit_of_tickets(): void
    {
        Storage::fake();
        $this->fakeTickets();
        $production = TicketingProduction::query()->create(['project_id' => $this->project->id]);
        foreach (range(1, TicketingProduction::MAX_IMAGES) as $index) {
            $production->images()->create(['path' => "picture-{$index}.jpg"]);
        }

        $this->post(route('projects.tabs.ticketing.production', $this->project), [
            'images' => [UploadedFile::fake()->image('one-too-many.jpg')],
        ], ['Accept' => 'application/json'])->assertUnprocessable()->assertJsonValidationErrors('images');

        $kept = $production->images()->firstOrFail();
        $this->post(route('projects.tabs.ticketing.production', $this->project), [
            'images' => [UploadedFile::fake()->image('replacement.jpg')],
            'remove_image_ids' => [$kept->id],
        ], ['Accept' => 'application/json'])->assertOk()->assertJsonCount(TicketingProduction::MAX_IMAGES, 'production.images');
    }

    #[Test]
    public function a_further_picture_becomes_the_cover_and_the_old_cover_a_further_picture(): void
    {
        Storage::fake();
        $imageId = '8b3f4e5d-6c7a-4b82-8c93-1d4e5f6a7b82';
        $this->fakeTickets([
            self::TICKETS_URL . '/api/integration/v1/productions/*/images/*' => Http::response(['removed' => true]),
            self::TICKETS_URL . '/api/integration/v1/productions/*/images' => Http::response(['id' => 'new-remote', 'url' => 'https://cdn.test/b.jpg', 'alt' => null]),
            self::TICKETS_URL . '/api/integration/v1/productions/*/hero' => Http::response(['url' => 'https://cdn.test/hero.jpg']),
        ]);
        Storage::put(TicketingProduction::HERO_DIRECTORY . '/old-cover.jpg', 'old');
        Storage::put(TicketingProduction::HERO_DIRECTORY . '/foyer.jpg', 'foyer');
        $production = TicketingProduction::query()->create([
            'project_id' => $this->project->id,
            'production_id' => self::PRODUCTION_ID,
            'hero_path' => 'old-cover.jpg',
            'hero_synced_at' => now(),
        ]);
        $foyer = $production->images()->create(['path' => 'foyer.jpg', 'remote_id' => $imageId]);

        $this->post(route('projects.tabs.ticketing.production', $this->project), [
            'cover_image_id' => $foyer->id,
        ], ['Accept' => 'application/json'])->assertOk();

        $production->refresh();
        $this->assertSame('foyer.jpg', $production->hero_path);
        $this->assertSame(['old-cover.jpg'], $production->images->pluck('path')->all());
        Storage::assertExists(TicketingProduction::HERO_DIRECTORY . '/old-cover.jpg');
        Storage::assertExists(TicketingProduction::HERO_DIRECTORY . '/foyer.jpg');
        Http::assertSent(fn (Request $request): bool => $request->method() === 'DELETE'
            && str_ends_with($request->url(), '/images/' . $imageId));
        Http::assertSent(fn (Request $request): bool => $request->method() === 'PUT'
            && str_ends_with($request->url(), '/productions/' . self::PRODUCTION_ID . '/hero'));
        $this->assertSame(1, $this->imagesPosted());
    }

    #[Test]
    public function the_old_cover_is_deleted_when_removed_alongside_the_new_one(): void
    {
        Storage::fake();
        $this->fakeTickets();
        Storage::put(TicketingProduction::HERO_DIRECTORY . '/old-cover.jpg', 'old');
        TicketingProduction::query()->create(['project_id' => $this->project->id, 'hero_path' => 'old-cover.jpg']);

        $this->post(route('projects.tabs.ticketing.production', $this->project), [
            'hero' => UploadedFile::fake()->image('premiere.jpg'),
            'remove_hero' => '1',
        ], ['Accept' => 'application/json'])->assertOk()->assertJsonCount(0, 'production.images');

        Storage::assertMissing(TicketingProduction::HERO_DIRECTORY . '/old-cover.jpg');
    }

    private function imagesPosted(): int
    {
        return Http::recorded(fn (Request $request): bool => $request->method() === 'POST'
            && str_ends_with($request->url(), '/images'))->count();
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

        $this->getJson(route('ticketing.sales', $event))
            ->assertOk()
            ->assertJsonPath('released', true)
            ->assertJsonPath('sold', 2)
            ->assertJsonPath('revenueCents', 5800)
            ->assertJsonPath('tickets.0.holderName', 'Ada Lovelace')
            ->assertJsonPath('tickets.0.priceCents', 2900);

        $this->getJson(route('ticketing.sales', $unreleased))
            ->assertOk()
            ->assertJsonPath('released', false);
    }

    #[Test]
    public function the_sales_need_the_project_to_be_visible(): void
    {
        $this->fakeTickets();
        $event = Event::factory()->create(['project_id' => $this->project->id, 'event_type_id' => $this->type->id]);
        $this->actingAsUserWith([]);

        $this->getJson(route('ticketing.sales', $event))->assertForbidden();
        Http::assertNothingSent();
    }

    #[Test]
    public function the_calendar_hint_counts_released_dates_in_one_call(): void
    {
        $this->fakeTickets();
        $released = Event::factory()->create([
            'project_id' => $this->project->id, 'event_type_id' => $this->type->id,
            'start_time' => '2027-01-10 19:30:00', 'end_time' => '2027-01-10 22:00:00',
        ]);
        Event::factory()->create([
            'project_id' => $this->project->id, 'event_type_id' => $this->type->id,
            'start_time' => '2027-01-11 19:30:00', 'end_time' => '2027-01-11 22:00:00',
        ]);
        TicketingEventRelease::query()->create([
            'event_id' => $released->id, 'state' => 'released', 'tickets_date_id' => self::DATE_ID, 'classes' => [],
        ]);

        $this->getJson(route('ticketing.calendar-summary', ['start_date' => '2027-01-01', 'end_date' => '2027-01-31']))
            ->assertOk()
            ->assertExactJson([(string) $released->id => ['sold' => 84, 'capacity' => 300, 'cancelled' => false]]);

        Http::assertSentCount(1);
        Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/dates?ids=' . self::DATE_ID));
    }

    #[Test]
    public function the_calendar_hint_leaves_out_projects_the_person_cannot_see(): void
    {
        $this->fakeTickets();
        $event = Event::factory()->create([
            'project_id' => $this->project->id, 'event_type_id' => $this->type->id,
            'start_time' => '2027-01-10 19:30:00', 'end_time' => '2027-01-10 22:00:00',
        ]);
        TicketingEventRelease::query()->create([
            'event_id' => $event->id, 'state' => 'released', 'tickets_date_id' => self::DATE_ID, 'classes' => [],
        ]);
        $this->actingAsUserWith([]);

        $this->getJson(route('ticketing.calendar-summary', ['start_date' => '2027-01-01', 'end_date' => '2027-01-31']))
            ->assertOk()
            ->assertExactJson([]);

        Http::assertNothingSent();
    }

    #[Test]
    public function opening_tickets_from_a_date_asks_for_a_login_link_to_it(): void
    {
        $this->fakeTickets();
        $event = Event::factory()->create(['project_id' => $this->project->id, 'event_type_id' => $this->type->id]);
        TicketingEventRelease::query()->create([
            'event_id' => $event->id, 'state' => 'released', 'tickets_date_id' => self::DATE_ID, 'classes' => [],
        ]);

        $this->get(route('ticketing.open', ['event' => $event->id]))
            ->assertRedirect(self::TICKETS_URL . '/api/auth/core-login/verify?token=t0k3n');

        Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/login-links')
            && $request['email'] === auth()->user()->email
            && $request['destination'] === ['type' => 'date', 'dateId' => self::DATE_ID]);
    }

    #[Test]
    public function opening_the_shop_appearance_asks_for_a_login_link_to_its_tab(): void
    {
        $this->fakeTickets();

        $this->get(route('ticketing.open', ['to' => 'settings', 'tab' => 'appearance']))
            ->assertRedirect(self::TICKETS_URL . '/api/auth/core-login/verify?token=t0k3n');

        Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/login-links')
            && $request['destination'] === ['type' => 'houseSettings', 'tab' => 'appearance']);
    }

    #[Test]
    public function opening_tickets_falls_back_to_the_dashboard_when_tickets_is_down(): void
    {
        $this->fakeTickets([
            self::TICKETS_URL . '/api/integration/v1/login-links' => Http::response([], 503),
        ]);

        $this->get(route('ticketing.open'))->assertRedirect(self::TICKETS_URL . '/dashboard');
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
    public function a_released_date_follows_its_event_when_it_moves(): void
    {
        $this->fakeTickets();
        $event = Event::factory()->create([
            'project_id' => $this->project->id, 'event_type_id' => $this->type->id, 'room_id' => $this->room->id,
            'start_time' => '2027-02-01 20:00:00', 'end_time' => '2027-02-01 22:00:00',
        ]);
        $this->postJson(route('projects.tabs.ticketing.release', $this->project), ['event_ids' => [$event->id]])->assertOk();

        $this->moveConfirmed($event, ['start_time' => '2027-02-02 20:00:00']);
        $this->runTicketingSync();

        $this->assertCount(2, Http::recorded(fn (Request $request): bool => $request->method() === 'PUT'
            && str_ends_with($request->url(), '/dates')));
        $this->assertStringContainsString('2027-02-02T20:00', Http::recorded()->last()[0]->data()['startsAt']);
    }

    #[Test]
    public function a_released_date_cannot_be_deleted_from_the_calendar(): void
    {
        $this->fakeTickets();
        $event = $this->releasedEvent();

        $this->delete(route('events.delete', $event))->assertRedirect()->assertSessionHas('error');
        $this->deleteJson(route('events.delete', $event))->assertStatus(422);
        $this->delete(route('projects.destroy', $this->project))->assertRedirect()->assertSessionHas('error');

        $this->assertNull($event->fresh()->deleted_at);
        $this->assertNull($this->project->fresh()->deleted_at);
        Http::assertNothingSent();
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
            'description' => null,
            'reductions' => [],
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
            'description' => null,
            'reductions' => [],
        ])->assertNotFound();

        $this->assertDatabaseMissing('ticketing_event_releases', ['event_id' => $own->id]);
    }

    #[Test]
    public function a_calendar_change_goes_through_when_tickets_is_unreachable(): void
    {
        $this->fakeTickets([self::TICKETS_URL . '/api/integration/v1/dates' => Http::failedConnection()]);
        $event = $this->releasedEvent();

        $this->moveConfirmed($event, ['start_time' => '2027-02-02 20:00:00']);
        $this->runTicketingSync();

        $this->assertSame('2027-02-02 20:00:00', $event->fresh()->start_time->format('Y-m-d H:i:s'));
        $this->assertNotNull($event->fresh()->ticketingRelease->sync_error);
    }

    #[Test]
    public function a_rejection_by_tickets_is_kept_on_the_date(): void
    {
        $message = 'The production sells in one room; this date lies in another one.';
        $this->fakeTickets([self::TICKETS_URL . '/api/integration/v1/dates' => Http::response([
            'error' => ['code' => 'CONFLICT', 'message' => $message],
        ], 409)]);
        $event = $this->releasedEvent();

        $this->moveConfirmed($event, ['start_time' => '2027-02-02 20:00:00']);
        $this->runTicketingSync();

        $this->assertSame($message, $event->fresh()->ticketingRelease->sync_error);
        $this->getJson(route('projects.tabs.ticketing', $this->project))
            ->assertJsonPath('events.0.release.syncError', $message);
    }

    #[Test]
    public function a_successful_sync_clears_the_error(): void
    {
        $this->fakeTickets();
        $event = $this->releasedEvent(['sync_error' => 'Artwork-Tickets could not be reached.']);

        $this->moveConfirmed($event, ['start_time' => '2027-02-02 20:00:00']);
        $this->runTicketingSync();

        $this->assertNull($event->fresh()->ticketingRelease->sync_error);
    }

    #[Test]
    public function nothing_is_sent_when_tickets_is_not_configured(): void
    {
        config()->set('services.tickets.url', null);
        Http::fake();
        $event = $this->releasedEvent();

        $event->update(['start_time' => '2027-02-02 20:00:00']);
        $event->delete();

        Bus::assertNotDispatched(SyncTicketingEventJob::class);
        Http::assertNothingSent();
        $this->assertSoftDeleted('events', ['id' => $event->id]);
    }

    #[Test]
    public function after_disconnecting_dates_once_on_sale_move_and_delete_freely(): void
    {
        Http::fake();
        $event = $this->releasedEvent();
        TicketingConnection::query()->delete();

        $this->postJson(route('events.multi-cell.move'), [
            'events' => [$event->id],
            'cell' => ['day' => '2027-02-03', 'room_id' => $this->room->id],
        ])->assertOk();
        $this->delete(route('projects.destroy', $this->project))->assertSessionMissing('error');

        Bus::assertNotDispatched(SyncTicketingEventJob::class);
        Http::assertNothingSent();
        $this->assertSoftDeleted('projects', ['id' => $this->project->id]);
    }

    #[Test]
    public function a_date_on_sale_moves_only_once_the_move_is_confirmed(): void
    {
        $this->fakeTickets();
        $event = $this->releasedEvent();
        $move = ['events' => [$event->id], 'cell' => ['day' => '2027-02-03', 'room_id' => $this->room->id]];

        $this->postJson(route('events.multi-cell.move'), $move)->assertStatus(422);
        $this->assertSame('2027-02-01', $event->fresh()->start_time->format('Y-m-d'));

        $this->postJson(route('events.multi-cell.move'), $move, [TicketingLock::MOVE_CONFIRMED_HEADER => '1'])->assertOk();
        $this->assertSame('2027-02-03', $event->fresh()->start_time->format('Y-m-d'));
    }

    #[Test]
    public function a_date_on_sale_does_not_move_without_the_permission(): void
    {
        $this->fakeTickets();
        $event = $this->releasedEvent();
        $this->actingAsUserWith([]);

        $this->expectException(TicketingLockedException::class);
        $this->moveConfirmed($event, ['start_time' => '2027-02-02 20:00:00']);
    }

    #[Test]
    public function the_permission_lets_someone_other_than_an_admin_move_a_date_on_sale(): void
    {
        $this->fakeTickets();
        $event = $this->releasedEvent();
        $this->actingAsUserWith([PermissionEnum::TICKETING_MOVE_ON_SALE->value]);

        $this->moveConfirmed($event, ['start_time' => '2027-02-02 20:00:00']);

        $this->assertSame('2027-02-02 20:00:00', $event->fresh()->start_time->format('Y-m-d H:i:s'));
    }

    #[Test]
    public function a_date_on_sale_only_moves_to_a_room_of_the_same_venue(): void
    {
        $this->fakeTickets();
        $event = $this->releasedEvent();
        $sameVenue = Room::factory()->create();
        TicketingRoomLink::query()->create(['room_id' => $sameVenue->id, 'venue_id' => self::VENUE_ID]);

        $this->moveConfirmed($event, ['room_id' => $sameVenue->id]);
        $this->assertSame($sameVenue->id, $event->fresh()->room_id);

        $this->expectException(TicketingLockedException::class);
        $this->moveConfirmed($event, ['room_id' => Room::factory()->create()->id]);
    }

    #[Test]
    public function without_a_person_a_date_on_sale_moves_unasked(): void
    {
        $this->fakeTickets();
        $event = $this->releasedEvent();
        Auth::forgetUser();

        $event->update(['start_time' => '2027-02-02 20:00:00']);

        $this->assertSame('2027-02-02 20:00:00', $event->fresh()->start_time->format('Y-m-d H:i:s'));
    }

    #[Test]
    public function the_move_check_names_the_dates_on_sale_of_the_series_with_their_sales(): void
    {
        $this->fakeTickets();
        [$first, $second] = $this->series();
        TicketingEventRelease::query()->create([
            'event_id' => $second->id, 'state' => 'released', 'tickets_date_id' => self::DATE_ID, 'classes' => [],
        ]);

        $this->getJson(route('ticketing.move-check', ['event_ids' => [$first->id]]))
            ->assertOk()
            ->assertExactJson(['may_move' => true, 'dates' => []]);

        $this->getJson(route('ticketing.move-check', ['event_ids' => [$first->id], 'with_series' => 1]))
            ->assertOk()
            ->assertJsonPath('may_move', true)
            ->assertJsonCount(1, 'dates')
            ->assertJsonPath('dates.0.id', $second->id)
            ->assertJsonPath('dates.0.sold', 84);
    }

    #[Test]
    public function the_move_check_still_asks_when_tickets_is_unreachable(): void
    {
        $this->fakeTickets([self::TICKETS_URL . '/api/integration/v1/dates?ids=*' => Http::failedConnection()]);
        $event = $this->releasedEvent();

        $this->getJson(route('ticketing.move-check', ['event_ids' => [$event->id]]))
            ->assertOk()
            ->assertJsonPath('dates.0.id', $event->id)
            ->assertJsonPath('dates.0.sold', null);
    }

    /**
     * Verschieben wie aus dem Kalender, nachdem die Person es bestätigt hat.
     *
     * @param array<string, mixed> $attributes
     */
    private function moveConfirmed(Event $event, array $attributes): void
    {
        request()->headers->set(TicketingLock::MOVE_CONFIRMED_HEADER, '1');
        $event->update($attributes);
    }

    /** Die Warteschlange ist im Test gefälscht; der Abgleich läuft hier von Hand. */
    private function runTicketingSync(): void
    {
        foreach (Bus::dispatched(SyncTicketingEventJob::class) as $job) {
            app()->call([$job, 'handle']);
        }
    }

    /** @param array<string, mixed> $release */
    private function releasedEvent(array $release = []): Event
    {
        $event = Event::factory()->create([
            'project_id' => $this->project->id, 'event_type_id' => $this->type->id, 'room_id' => $this->room->id,
            'start_time' => '2027-02-01 20:00:00', 'end_time' => '2027-02-01 22:00:00',
        ]);
        TicketingProduction::query()->create([
            'project_id' => $this->project->id, 'production_id' => self::PRODUCTION_ID,
        ]);
        TicketingEventRelease::query()->create($release + [
            'event_id' => $event->id, 'state' => 'released', 'tickets_date_id' => self::DATE_ID,
            'classes' => [['zone_key' => 'parkett', 'name' => 'Parkett', 'price_cents' => 2900, 'quota' => 300]],
        ]);

        return $event;
    }
}
