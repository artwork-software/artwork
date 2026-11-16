<?php

namespace Tests\Feature\Ticketing;

use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\EventType\Models\EventType;
use Artwork\Modules\Project\Enum\ProjectTabComponentEnum;
use Artwork\Modules\Project\Enum\ProjectTabComponentPermissionEnum;
use Artwork\Modules\Project\Models\Component;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\Ticketing\Models\TicketingConnection;
use Artwork\Modules\Ticketing\Models\TicketingEventRelease;
use Artwork\Modules\Ticketing\Models\TicketingProduction;
use Artwork\Modules\Ticketing\Models\TicketingRoomLink;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\Fluent\AssertableJson;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\ActsAsRole;
use Tests\Feature\FeatureTestCase;

/** Die Ticketing-Komponente im Projekt: welche Termine erscheinen, mit welchen Vorgaben. */
final class TicketingProjectTabTest extends FeatureTestCase
{
    use ActsAsRole;

    private const TICKETS_URL = 'https://tickets.test';
    private const VENUE_ID = '0355e5ad-e8aa-405b-9550-038a557dc897';

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.tickets.url', self::TICKETS_URL);
        config()->set('services.tickets.provisioning_secret', str_repeat('s', 40));
    }

    private function connect(): void
    {
        TicketingConnection::query()->create([
            'tickets_url' => self::TICKETS_URL,
            'organization_id' => 'org_1',
            'organization_slug' => 'theater',
            'dashboard_url' => self::TICKETS_URL . '/dashboard',
            'api_key' => 'tk_plain',
            'oauth_client_id' => 1,
            'connected_by_user_id' => null,
        ]);
    }

    #[Test]
    public function only_dates_of_selling_event_types_are_listed_with_venue_defaults(): void
    {
        $this->connect();
        Http::fake([
            self::TICKETS_URL . '/api/integration/v1/house/billing' => Http::response(['profile' => [], 'legalComplete' => true, 'shopLegalComplete' => true, 'payoutAccount' => 'verified', 'platformTerms' => ['accepted' => true]]),
            self::TICKETS_URL . '/api/integration/v1/reductions' => Http::response(['reductions' => [
                ['id' => '3b9f1a2c-0d4e-4f5a-8b6c-7d8e9f0a1b2c', 'name' => 'Ermäßigt', 'kind' => 'percent', 'value' => 5000, 'requiresProof' => true, 'defaultEnabled' => true],
            ]]),
            self::TICKETS_URL . '/api/integration/v1/venues' => Http::response(['venues' => [[
                'id' => self::VENUE_ID,
                'name' => 'Großer Saal',
                'street' => '', 'postalCode' => '', 'city' => '', 'country' => 'DE', 'seated' => true,
                'zones' => [
                    ['key' => 'parkett', 'name' => 'Parkett', 'capacity' => 300, 'defaultPriceCents' => 2900],
                    ['key' => 'rang', 'name' => 'Rang', 'capacity' => 80, 'defaultPriceCents' => null],
                ],
            ]]]),
        ]);

        $this->actingAsAdmin();
        $project = Project::factory()->create();
        $room = Room::factory()->create();
        TicketingRoomLink::query()->create(['room_id' => $room->id, 'venue_id' => self::VENUE_ID]);
        $selling = EventType::factory()->create(['relevant_for_ticketing' => true, 'name' => 'Vorstellung']);
        $internal = EventType::factory()->create(['relevant_for_ticketing' => false]);

        $show = Event::factory()->create([
            'project_id' => $project->id, 'event_type_id' => $selling->id, 'room_id' => $room->id, 'eventName' => 'Premiere',
        ]);
        Event::factory()->create(['project_id' => $project->id, 'event_type_id' => $internal->id, 'room_id' => $room->id]);
        TicketingEventRelease::query()->create([
            'event_id' => $show->id, 'classes' => [['zone_key' => 'parkett', 'name' => 'Parkett', 'price_cents' => 3500, 'quota' => 250]],
        ]);

        $response = $this->getJson(route('projects.tabs.ticketing', $project))->assertOk();

        $response->assertJsonPath('connection.connected', true)
            ->assertJsonPath('hasSellingEventTypes', true)
            ->assertJsonPath('ticketsError', null)
            ->assertJsonPath('production.linked', false)
            ->assertJsonPath('production.fallback.title', $project->name)
            ->assertJsonPath('reductions.0.name', 'Ermäßigt')
            ->assertJsonCount(1, 'events')
            ->assertJsonPath('events.0.name', 'Premiere')
            ->assertJsonPath('events.0.venue.capacity', 380)
            ->assertJsonPath('events.0.venue.seated', true)
            ->assertJsonPath('events.0.venue.zones.0.defaultPriceCents', 2900)
            ->assertJsonPath('events.0.release.classes.0.quota', 300)
            ->assertJsonPath('events.0.release.classes.0.price_cents', 3500)
            ->assertJsonPath('events.0.release.state', 'draft');
    }

    #[Test]
    public function reductions_carry_the_value_and_classes_the_production_grants_them_on(): void
    {
        $this->connect();
        $reduced = '3b9f1a2c-0d4e-4f5a-8b6c-7d8e9f0a1b2c';
        Http::fake([
            self::TICKETS_URL . '/api/integration/v1/house/billing' => Http::response(['profile' => []]),
            self::TICKETS_URL . '/api/integration/v1/venues' => Http::response(['venues' => []]),
            self::TICKETS_URL . '/api/integration/v1/reductions' => Http::response(['reductions' => [
                ['id' => $reduced, 'name' => 'Ermäßigt', 'kind' => 'percent', 'value' => 5000, 'requiresProof' => true, 'defaultEnabled' => true],
            ]]),
            self::TICKETS_URL . '/api/integration/v1/productions/*' => Http::response([
                'status' => 'published',
                'shopUrl' => 'https://tickets.test/theater/hamlet',
                'reductions' => [
                    ['reductionTypeId' => $reduced, 'name' => 'Ermäßigt', 'kind' => 'fixed', 'value' => 300, 'enabled' => true, 'categories' => ['Parkett']],
                ],
            ]),
        ]);

        $this->actingAsAdmin();
        $project = Project::factory()->create();
        TicketingProduction::query()->create(['project_id' => $project->id, 'production_id' => '6f0f3c1e-1b1a-4a8e-9d55-1f2a3b4c5d6e']);

        $this->getJson(route('projects.tabs.ticketing', $project))
            ->assertOk()
            ->assertJsonPath('reductions.0.kind', 'fixed')
            ->assertJsonPath('reductions.0.value', 300)
            ->assertJsonPath('reductions.0.categories', ['Parkett']);
    }

    #[Test]
    public function the_component_and_the_ticketing_ui_appear_only_once_connected(): void
    {
        $this->actingAsAdmin();
        Component::query()->create([
            'name' => 'Artwork-Tickets',
            'type' => ProjectTabComponentEnum::TICKETING,
            'data' => ['icon' => 'IconBuildingStore'],
            'special' => true,
            'sidebar_enabled' => false,
            'permission_type' => ProjectTabComponentPermissionEnum::PERMISSION_TYPE_ALL_SEE_AND_EDIT->value,
        ]);
        $offered = static fn ($components): bool => collect($components)
            ->contains('type', ProjectTabComponentEnum::TICKETING->value);

        $this->get(route('tab.index'))->assertInertia(fn (AssertableInertia $page) => $page
            ->where('ticketing.active', false)
            ->where('componentsSpecial', fn ($components): bool => !$offered($components)));

        $this->connect();

        $this->get(route('tab.index'))->assertInertia(fn (AssertableInertia $page) => $page
            ->where('ticketing.active', true)
            ->where('componentsSpecial', $offered));
    }

    #[Test]
    public function without_a_connection_no_call_is_made_and_the_state_is_reported(): void
    {
        Http::fake();
        $this->actingAsAdmin();
        $project = Project::factory()->create();

        $this->getJson(route('projects.tabs.ticketing', $project))
            ->assertOk()
            ->assertJsonPath('connection.connected', false)
            ->assertJsonPath('events', []);

        Http::assertNothingSent();
    }

    #[Test]
    public function an_unreachable_tickets_still_lists_the_dates(): void
    {
        $this->connect();
        Http::fake([
            self::TICKETS_URL . '/api/integration/v1/house/billing' => Http::response(['profile' => [], 'legalComplete' => true, 'shopLegalComplete' => true, 'payoutAccount' => 'verified', 'platformTerms' => ['accepted' => true]]),
            self::TICKETS_URL . '/api/integration/v1/venues' => Http::response(null, 503),
            self::TICKETS_URL . '/api/integration/v1/reductions' => Http::response(['reductions' => []]),
        ]);
        $this->actingAsAdmin();
        $project = Project::factory()->create();
        $selling = EventType::factory()->create(['relevant_for_ticketing' => true]);
        Event::factory()->create(['project_id' => $project->id, 'event_type_id' => $selling->id]);

        $this->getJson(route('projects.tabs.ticketing', $project))
            ->assertOk()
            ->assertJsonCount(1, 'events')
            ->assertJsonPath('events.0.venue', null)
            ->assertJson(fn (AssertableJson $json) => $json->whereType('ticketsError', 'string')->etc());
    }
}
