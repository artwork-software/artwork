<?php

namespace Tests\Feature\Ticketing;

use Artwork\Modules\GeneralSettings\Models\GeneralSettings;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\Ticketing\Models\TicketingConnection;
use Artwork\Modules\Ticketing\Models\TicketingRoomLink;
use Artwork\Modules\Ticketing\Services\TicketingConnectionService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Laravel\Passport\Client;
use Laravel\Passport\Passport;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\ActsAsRole;
use Tests\Feature\FeatureTestCase;

/**
 * Handshake mit artwork tickets: OAuth-Client raus, Hausschlüssel rein.
 */
final class TicketingConnectionTest extends FeatureTestCase
{
    use ActsAsRole;

    private const TICKETS_URL = 'https://tickets.test';

    /** @return array<string, mixed> */
    private function draft(array $rooms = [], array $reductions = [], array $billing = []): array
    {
        return [
            'house' => ['name' => 'Theater Süd', 'slug' => 'theater-sued'],
            'billing' => $billing + self::billing(),
            'rooms' => $rooms,
            'reductions' => $reductions,
        ];
    }

    /** Vollständige Angaben, wie der Assistent sie schickt. */
    private static function billing(): array
    {
        return [
            'legal_name' => 'Theater Süd gGmbH',
            'legal_form' => 'ggmbh',
            'street' => 'Theaterstraße 1',
            'postal_code' => '20095',
            'city' => 'Hamburg',
            'country' => 'DE',
            'register_number' => 'HRB 12345',
            'register_court' => 'Hamburg',
            'vat_id' => 'DE123456789',
            'tax_number' => null,
            'contact_name' => 'Erika Muster',
            'contact_phone' => '+49 40 123456',
            'website' => null,
            'account_holder' => 'Theater Süd gGmbH',
            'iban' => 'DE89 3704 0044 0532 0130 00',
        ];
    }

    /** @param array<string, mixed> $stubs Antworten je URL, die die glücklichen Vorgaben ersetzen */
    private function fakeHappyTickets(array $stubs = []): void
    {
        Http::fake($stubs + [
            self::TICKETS_URL . '/api/integration/v1/houses' => Http::response([
                'organizationId' => 'org_1',
                'slug' => 'theater-sued',
                'apiKey' => 'tk_plain',
                'dashboardUrl' => self::TICKETS_URL . '/dashboard',
            ]),
            self::TICKETS_URL . '/api/integration/v1/venues' => Http::response([
                'venues' => [['externalId' => '7', 'venueId' => '0355e5ad-e8aa-405b-9550-038a557dc897']],
            ]),
            self::TICKETS_URL . '/api/integration/v1/reductions' => Http::response(['reductions' => []]),
            self::TICKETS_URL . '/api/integration/v1/availability*' => Http::response(['slug' => ['available' => true]]),
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.tickets.url', self::TICKETS_URL);
        config()->set('services.tickets.provisioning_secret', str_repeat('s', 40));
    }

    #[Test]
    public function connecting_provisions_a_house_and_stores_both_credentials(): void
    {
        $this->fakeHappyTickets();

        $user = $this->actingAsUserWith(PermissionEnum::TICKETING_MANAGE->value);
        $room = Room::factory()->create(['id' => 7, 'name' => 'Großer Saal']);

        $this->post(route('settings.tickets.connect'), $this->draft(
            rooms: [[
                'id' => $room->id,
                'street' => 'Theaterstraße 1', 'postal_code' => '20095', 'city' => 'Hamburg', 'country' => 'DE',
                'zones' => [['name' => 'Parkett', 'capacity' => 300, 'default_price_cents' => 2900]],
            ]],
            reductions: [['name' => 'Ermäßigt', 'kind' => 'percent', 'value' => 5000, 'requires_proof' => true, 'default_enabled' => true]],
        ))
            ->assertRedirect()
            ->assertSessionHas('success');

        Http::assertSent(function (Request $request) use ($user): bool {
            return str_ends_with($request->url(), '/api/integration/v1/houses')
                && $request->hasHeader('Authorization', 'Bearer ' . str_repeat('s', 40))
                && $request['slug'] === 'theater-sued'
                && $request['ownerEmail'] === $user->email
                && $request['coreUrl'] === config('app.url')
                && $request['billing']['legalName'] === 'Theater Süd gGmbH'
                && $request['billing']['legalForm'] === 'ggmbh'
                && $request['billing']['iban'] === 'DE89370400440532013000'
                && $request['billing']['taxNumber'] === null
                && (string) $request['coreClientId'] === (string) DB::table('oauth_clients')->value('id')
                && is_string($request['coreClientSecret']) && $request['coreClientSecret'] !== '';
        });
        Http::assertSent(
            fn (Request $request): bool => str_ends_with($request->url(), '/api/integration/v1/venues')
                && $request->hasHeader('x-api-key', 'tk_plain')
                && $request['venues'][0]['externalId'] === '7'
                && $request['venues'][0]['street'] === 'Theaterstraße 1'
                && $request['venues'][0]['postalCode'] === '20095'
                && $request['venues'][0]['city'] === 'Hamburg'
                && $request['venues'][0]['country'] === 'DE'
                && $request['venues'][0]['zones'][0]['defaultPriceCents'] === 2900
        );
        Http::assertSent(
            fn (Request $request): bool => str_ends_with($request->url(), '/api/integration/v1/reductions')
                && $request['reductions'][0]['value'] === 5000
        );
        $this->assertDatabaseHas('ticketing_room_links', [
            'room_id' => 7,
            'venue_id' => '0355e5ad-e8aa-405b-9550-038a557dc897',
        ]);

        $connection = TicketingConnection::query()->firstOrFail();
        $this->assertSame('org_1', $connection->organization_id);
        $this->assertSame('tk_plain', $connection->api_key);
        $this->assertNotSame('tk_plain', DB::table('ticketing_connections')->value('api_key'));
        $this->assertDatabaseHas('oauth_clients', [
            'id' => $connection->oauth_client_id,
            'name' => TicketingConnectionService::CLIENT_NAME,
            'revoked' => false,
        ]);
        $this->assertTrue(Client::query()->findOrFail($connection->oauth_client_id)->hasGrantType('client_credentials'));
    }

    #[Test]
    public function a_rejected_handshake_revokes_the_client_and_stores_nothing(): void
    {
        Http::fake([
            self::TICKETS_URL . '/api/integration/v1/houses' => Http::response([
                'error' => ['code' => 'CONFLICT', 'message' => 'This e-mail address already belongs to a ticket house.'],
            ], 409),
        ]);

        $this->actingAsUserWith(PermissionEnum::TICKETING_MANAGE->value);

        $this->post(route('settings.tickets.connect'), $this->draft())
            ->assertRedirect()
            ->assertSessionHas('error', 'This e-mail address already belongs to a ticket house.');

        $this->assertDatabaseCount('ticketing_connections', 0);
        $this->assertDatabaseHas('oauth_clients', ['name' => TicketingConnectionService::CLIENT_NAME, 'revoked' => true]);
    }

    #[Test]
    public function disconnecting_revokes_the_client_and_forgets_the_house(): void
    {
        $this->fakeHappyTickets();

        $this->actingAsUserWith(PermissionEnum::TICKETING_MANAGE->value);
        $this->post(route('settings.tickets.connect'), $this->draft());
        $clientId = TicketingConnection::query()->firstOrFail()->oauth_client_id;

        $this->delete(route('settings.tickets.disconnect'))->assertRedirect();

        $this->assertDatabaseCount('ticketing_connections', 0);
        $this->assertDatabaseHas('oauth_clients', ['id' => $clientId, 'revoked' => true]);
    }

    #[Test]
    public function the_settings_page_shows_the_connection_state(): void
    {
        $this->actingAsUserWith(PermissionEnum::TICKETING_MANAGE->value);

        $this->get(route('settings.tickets'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Settings/Tickets/Index')
                ->where('connection.configured', true)
                ->where('connection.connected', false));
    }

    #[Test]
    public function tool_settings_alone_do_not_grant_access(): void
    {
        $this->actingAsUserWith(PermissionEnum::SETTINGS_UPDATE->value);

        $this->get(route('settings.tickets'))->assertForbidden();
        $this->post(route('settings.tickets.connect'), $this->draft())->assertForbidden();
        $this->delete(route('settings.tickets.disconnect'))->assertForbidden();
    }

    #[Test]
    public function an_unconfigured_installation_cannot_connect(): void
    {
        config()->set('services.tickets.url', null);
        Http::fake();

        $this->actingAsUserWith(PermissionEnum::TICKETING_MANAGE->value);

        $this->post(route('settings.tickets.connect'), $this->draft())->assertSessionHas('error');

        Http::assertNothingSent();
        $this->assertDatabaseCount('ticketing_connections', 0);
    }

    #[Test]
    public function tickets_can_exchange_the_client_for_a_scoped_token(): void
    {
        if (!file_exists(Passport::keyPath('oauth-private.key'))) {
            $this->artisan('passport:keys');
        }

        $this->fakeHappyTickets();

        $this->actingAsUserWith(PermissionEnum::TICKETING_MANAGE->value);
        $this->post(route('settings.tickets.connect'), $this->draft());

        // Das Geheimnis liegt nur bei tickets; hier wird es aus der gefakten Anfrage zurückgeholt.
        $secret = null;
        Http::assertSent(function (Request $request) use (&$secret): bool {
            if (str_ends_with($request->url(), '/houses')) {
                $secret = $request['coreClientSecret'];
            }

            return true;
        });

        $token = $this->postJson('/oauth/token', [
            'grant_type' => 'client_credentials',
            'client_id' => TicketingConnection::query()->firstOrFail()->oauth_client_id,
            'client_secret' => $secret,
            'scope' => 'ticketing:read ticketing:write',
        ])->assertOk()->json();

        $this->assertSame('Bearer', $token['token_type']);
        $this->assertNotEmpty($token['access_token']);
    }

    #[Test]
    public function the_availability_check_is_proxied_to_tickets_with_the_own_address(): void
    {
        Http::fake([self::TICKETS_URL . '/api/integration/v1/availability*' => Http::response([
            'slug' => ['available' => false, 'reason' => 'taken'],
            'name' => ['available' => true],
            'owner' => ['available' => false],
        ])]);
        $user = $this->actingAsUserWith(PermissionEnum::TICKETING_MANAGE->value);

        $this->getJson(route('settings.tickets.availability', ['slug' => 'theater', 'name' => 'Theater']))
            ->assertOk()
            ->assertJsonPath('slug.reason', 'taken')
            ->assertJsonPath('owner.available', false);

        Http::assertSent(fn (Request $request): bool => $request['slug'] === 'theater'
            && $request['name'] === 'Theater'
            && $request['ownerEmail'] === $user->email
            && $request->hasHeader('Authorization', 'Bearer ' . str_repeat('s', 40)));
    }

    #[Test]
    public function a_draft_with_an_invalid_address_or_reduction_is_refused_before_any_call(): void
    {
        Http::fake();
        $this->actingAsUserWith(PermissionEnum::TICKETING_MANAGE->value);

        $this->from(route('settings.tickets'))
            ->post(route('settings.tickets.connect'), [
                'house' => ['name' => 'Theater', 'slug' => 'Theater Süd'],
                'rooms' => [],
                'reductions' => [['name' => 'Zu viel', 'kind' => 'percent', 'value' => 12000, 'requires_proof' => true, 'default_enabled' => false]],
            ])
            ->assertSessionHasErrors(['house.slug', 'reductions.0.value']);

        Http::assertNothingSent();
        $this->assertDatabaseCount('ticketing_connections', 0);
    }

    #[Test]
    public function a_draft_without_the_details_or_with_a_wrong_iban_is_refused_before_any_call(): void
    {
        Http::fake();
        $this->actingAsUserWith(PermissionEnum::TICKETING_MANAGE->value);

        $this->from(route('settings.tickets'))
            ->post(route('settings.tickets.connect'), $this->draft(billing: [
                'legal_form' => 'ag',
                'vat_id' => null,
                'tax_number' => null,
                'iban' => 'DE88 3704 0044 0532 0130 00',
            ]))
            ->assertSessionHasErrors(['billing.legal_form', 'billing.vat_id', 'billing.tax_number', 'billing.iban']);

        Http::assertNothingSent();
    }

    #[Test]
    public function the_details_may_be_left_for_later_and_the_overview_then_warns(): void
    {
        $this->fakeHappyTickets([
            self::TICKETS_URL . '/api/integration/v1/house/billing' => Http::response(['profile' => [], 'legalComplete' => false, 'bankComplete' => false]),
        ]);
        $this->actingAsUserWith(PermissionEnum::TICKETING_MANAGE->value);

        $this->from(route('settings.tickets'))
            ->post(route('settings.tickets.connect'), ['billing' => null] + $this->draft())
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        Http::assertSent(static fn (Request $request): bool => str_ends_with($request->url(), '/houses')
            && array_key_exists('billing', $request->data())
            && $request['billing'] === null);

        $this->get(route('settings.tickets'))
            ->assertInertia(fn ($page) => $page
                ->where('connection.connected', true)
                ->where('connection.billingComplete', false));
    }

    #[Test]
    public function the_house_defaults_prefill_the_details_from_the_letterhead(): void
    {
        $user = $this->actingAsUserWith(PermissionEnum::TICKETING_MANAGE->value);
        $settings = app(GeneralSettings::class);
        $settings->letterhead_name = 'Theater Süd gGmbH';
        $settings->letterhead_street = 'Theaterstraße 1';
        $settings->letterhead_zip_code = '20095';
        $settings->letterhead_city = 'Hamburg';
        $settings->save();

        $this->get(route('settings.tickets'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Settings/Tickets/Index')
                ->where('houseDefaults.billing.legal_name', 'Theater Süd gGmbH')
                ->where('houseDefaults.billing.account_holder', 'Theater Süd gGmbH')
                ->where('houseDefaults.billing.street', 'Theaterstraße 1')
                ->where('houseDefaults.billing.contact_name', $user->full_name)
                ->where('houseDefaults.billing.iban', ''));
    }

    #[Test]
    public function the_house_defaults_carry_the_letterhead_address(): void
    {
        $this->actingAsUserWith(PermissionEnum::TICKETING_MANAGE->value);
        $settings = app(GeneralSettings::class);
        $settings->letterhead_street = 'Theaterstraße 1';
        $settings->letterhead_zip_code = '20095';
        $settings->letterhead_city = 'Hamburg';
        $settings->save();

        $this->get(route('settings.tickets'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Settings/Tickets/Index')
                ->where('houseDefaults.address', [
                    'street' => 'Theaterstraße 1', 'postal_code' => '20095', 'city' => 'Hamburg', 'country' => 'DE',
                ]));
    }

    #[Test]
    public function the_rooms_tab_shows_each_room_with_its_venue_and_forgets_stale_links(): void
    {
        $this->fakeHappyTickets([
            self::TICKETS_URL . '/api/integration/v1/venues' => Http::response(['venues' => [[
                'id' => '0355e5ad-e8aa-405b-9550-038a557dc897',
                'name' => 'Großer Saal',
                'street' => 'Theaterstraße 1', 'postalCode' => '20095', 'city' => 'Hamburg', 'country' => 'DE',
                'zones' => [['name' => 'Parkett', 'capacity' => 300, 'defaultPriceCents' => 2900]],
            ]]]),
        ]);
        $this->actingAsUserWith(PermissionEnum::TICKETING_MANAGE->value);
        $this->post(route('settings.tickets.connect'), $this->draft());
        Room::factory()->create(['id' => 7, 'name' => 'Großer Saal']);
        Room::factory()->create(['id' => 8, 'name' => 'Studio']);
        TicketingRoomLink::query()->create(['room_id' => 7, 'venue_id' => '0355e5ad-e8aa-405b-9550-038a557dc897']);
        TicketingRoomLink::query()->create(['room_id' => 8, 'venue_id' => '9b3d8a1e-1c8e-4d0f-9d1a-000000000000']);

        $this->get(route('settings.tickets.rooms'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Settings/Tickets/Rooms')
                ->where('ticketsError', null)
                ->where('rooms.0.venue.zones.0.name', 'Parkett')
                ->where('rooms.1.venue', null));

        Http::assertSent(fn (Request $request): bool => $request->method() === 'GET'
            && str_ends_with($request->url(), '/api/integration/v1/venues')
            && $request->hasHeader('x-api-key', 'tk_plain'));
        $this->assertDatabaseMissing('ticketing_room_links', ['room_id' => 8]);
    }

    #[Test]
    public function the_rooms_tab_still_renders_when_tickets_is_unreachable(): void
    {
        $this->fakeHappyTickets([self::TICKETS_URL . '/api/integration/v1/venues' => Http::response(null, 503)]);
        $this->actingAsUserWith(PermissionEnum::TICKETING_MANAGE->value);
        $this->post(route('settings.tickets.connect'), $this->draft());

        $this->get(route('settings.tickets.rooms'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Settings/Tickets/Rooms')
                ->where('connection.connected', true)
                ->whereNot('ticketsError', null));
    }

    #[Test]
    public function rooms_can_be_synced_again_after_connecting(): void
    {
        $this->fakeHappyTickets();
        $this->actingAsUserWith(PermissionEnum::TICKETING_MANAGE->value);
        $this->post(route('settings.tickets.connect'), $this->draft());
        $room = Room::factory()->create(['id' => 7, 'name' => 'Großer Saal']);
        TicketingRoomLink::query()->create(['room_id' => 7, 'venue_id' => '0355e5ad-e8aa-405b-9550-038a557dc897']);

        $this->from(route('settings.tickets.rooms'))
            ->post(route('settings.tickets.rooms.sync'), ['rooms' => [[
                'id' => $room->id,
                'street' => 'Neue Straße 2', 'postal_code' => '20097', 'city' => 'Hamburg', 'country' => 'AT',
                'zones' => [['name' => 'Rang', 'capacity' => 80, 'default_price_cents' => null]],
            ]]])
            ->assertRedirect(route('settings.tickets.rooms'))
            ->assertSessionHas('success');

        Http::assertSent(fn (Request $request): bool => $request->method() === 'POST'
            && str_ends_with($request->url(), '/api/integration/v1/venues')
            && $request['venues'][0]['venueId'] === '0355e5ad-e8aa-405b-9550-038a557dc897'
            && $request['venues'][0]['street'] === 'Neue Straße 2'
            && $request['venues'][0]['country'] === 'AT'
            && $request['venues'][0]['zones'][0]['name'] === 'Rang');
    }

    #[Test]
    public function a_room_with_an_unknown_country_is_refused(): void
    {
        $this->fakeHappyTickets();
        $this->actingAsUserWith(PermissionEnum::TICKETING_MANAGE->value);
        $this->post(route('settings.tickets.connect'), $this->draft());
        $room = Room::factory()->create();

        $this->from(route('settings.tickets.rooms'))
            ->post(route('settings.tickets.rooms.sync'), ['rooms' => [[
                'id' => $room->id, 'street' => null, 'postal_code' => null, 'city' => null, 'country' => 'US',
                'zones' => [['name' => 'Parkett', 'capacity' => 1, 'default_price_cents' => null]],
            ]]])
            ->assertSessionHasErrors(['rooms.0.country'])
            ->assertSessionDoesntHaveErrors(['rooms.0.street', 'rooms.0.postal_code', 'rooms.0.city']);
    }

    #[Test]
    public function an_empty_address_is_sent_as_empty_strings(): void
    {
        $this->fakeHappyTickets();
        $this->actingAsUserWith(PermissionEnum::TICKETING_MANAGE->value);
        $this->post(route('settings.tickets.connect'), $this->draft());
        $room = Room::factory()->create();

        $this->post(route('settings.tickets.rooms.sync'), ['rooms' => [[
            'id' => $room->id, 'street' => '', 'postal_code' => '', 'city' => '', 'country' => 'DE',
            'zones' => [['name' => 'Parkett', 'capacity' => 1, 'default_price_cents' => null]],
        ]]])->assertSessionHasNoErrors();

        Http::assertSent(fn (Request $request): bool => $request->method() === 'POST'
            && str_ends_with($request->url(), '/api/integration/v1/venues')
            && $request['venues'][0]['street'] === ''
            && $request['venues'][0]['city'] === '');
    }

    #[Test]
    public function syncing_without_a_connection_is_refused(): void
    {
        Http::fake();
        $this->actingAsUserWith(PermissionEnum::TICKETING_MANAGE->value);

        $this->post(route('settings.tickets.rooms.sync'), ['rooms' => []])->assertStatus(409);
        $this->post(route('settings.tickets.reductions.sync'), ['reductions' => []])->assertStatus(409);
        Http::assertNothingSent();
    }

    #[Test]
    public function the_reductions_tab_lists_what_tickets_holds_and_syncs_back(): void
    {
        $this->fakeHappyTickets([
            self::TICKETS_URL . '/api/integration/v1/reductions' => Http::response(['reductions' => [
                ['id' => 'r1', 'name' => 'Ermäßigt', 'kind' => 'percent', 'value' => 5000, 'requiresProof' => true, 'defaultEnabled' => true],
            ]]),
        ]);
        $this->actingAsUserWith(PermissionEnum::TICKETING_MANAGE->value);
        $this->post(route('settings.tickets.connect'), $this->draft());

        $this->get(route('settings.tickets.reductions'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Settings/Tickets/Reductions')
                ->where('reductions.0.name', 'Ermäßigt'));

        $this->from(route('settings.tickets.reductions'))
            ->post(route('settings.tickets.reductions.sync'), ['reductions' => [
                ['name' => 'Ermäßigt', 'kind' => 'fixed', 'value' => 500, 'requires_proof' => false, 'default_enabled' => true],
            ]])
            ->assertRedirect(route('settings.tickets.reductions'))
            ->assertSessionHas('success');

        Http::assertSent(fn (Request $request): bool => $request->method() === 'POST'
            && str_ends_with($request->url(), '/api/integration/v1/reductions')
            && $request['reductions'][0]['kind'] === 'fixed'
            && $request['reductions'][0]['value'] === 500);
    }
}
