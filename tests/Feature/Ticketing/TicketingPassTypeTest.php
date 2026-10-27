<?php

namespace Tests\Feature\Ticketing;

use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Ticketing\Models\TicketingConnection;
use Artwork\Modules\User\Models\User;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\ActsAsRole;
use Tests\Feature\FeatureTestCase;

/**
 * Tab "Karten & Abos": Mehrfachkarten und Abos des Ticket-Hauses — tickets hält sie, hier wird gepflegt.
 */
final class TicketingPassTypeTest extends FeatureTestCase
{
    use ActsAsRole;

    private const TICKETS_URL = 'https://tickets.test';

    private const PASS_TYPE_ID = '0b9f1c52-6a3e-4c1f-9a51-3f1a2b4c5d6e';

    private const PRODUCTION_ID = '5d2c7a10-1b2c-4d3e-8f90-a1b2c3d4e5f6';

    private const GROUP_ID = '7a1e2b3c-4d5e-4f60-9a1b-2c3d4e5f6a7b';

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.tickets.url', self::TICKETS_URL);
        config()->set('services.tickets.provisioning_secret', str_repeat('s', 40));
    }

    private function connect(): TicketingConnection
    {
        return TicketingConnection::query()->create([
            'tickets_url' => self::TICKETS_URL,
            'organization_id' => 'org_1',
            'organization_slug' => 'theater-sued',
            'dashboard_url' => self::TICKETS_URL . '/dashboard',
            'api_key' => 'tk_plain',
            'oauth_client_id' => '1',
            'connected_by_user_id' => User::factory()->create()->id,
        ]);
    }

    /** @return array<string, mixed> */
    private function card(array $overrides = []): array
    {
        return [
            'kind' => 'card',
            'name' => '10er-Karte',
            'description' => null,
            'price' => 9000,
            'uses' => 10,
            'monthlyPrice' => null,
            'upfrontPrice' => null,
            'termMode' => 'months',
            'termMonths' => null,
            'seasonEnds' => null,
            'saleEnds' => '2027-06-30',
            'afterTerm' => 'ends',
            'usesWindow' => 'month',
            'rolloverMax' => 0,
            'taxTreatment' => null,
            'validityDays' => 365,
            'validUntil' => null,
            'discountKind' => 'percent',
            'discountPercent' => 100,
            'discountAmount' => null,
            'maxUsesPerOrder' => null,
            'maxUsesPerEvent' => null,
            'maxUsesPerPeriod' => 1,
            'limitPeriod' => 'week',
            'redeemableInCheckout' => true,
            'stampable' => true,
            'allEvents' => false,
            'eventIds' => [self::PRODUCTION_ID],
            'groupIds' => [],
            ...$overrides,
        ];
    }

    #[Test]
    public function the_tab_shows_the_pass_types_and_productions_from_tickets(): void
    {
        $this->connect();
        $this->actingAsUserWith(PermissionEnum::TICKETING_MANAGE->value);

        Http::fake([
            self::TICKETS_URL . '/api/integration/v1/pass-types' => Http::response([
                'passTypes' => [['id' => self::PASS_TYPE_ID, 'status' => 'published', 'issuedCount' => 3, ...$this->card()]],
                'productions' => [['id' => self::PRODUCTION_ID, 'title' => 'Faust']],
                'groups' => [[
                    'id' => self::GROUP_ID,
                    'name' => 'Workshops',
                    'eventIds' => [self::PRODUCTION_ID],
                    'sold' => true,
                ]],
            ]),
        ]);

        $this->get(route('settings.tickets.passes'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Settings/Tickets/Passes')
                ->where('connection.connected', true)
                ->where('passTypes.0.id', self::PASS_TYPE_ID)
                ->where('passTypes.0.issuedCount', 3)
                ->where('productions.0.title', 'Faust')
                ->where('groups.0.name', 'Workshops')
                ->where('groups.0.sold', true)
                ->where('ticketsError', null));

        Http::assertSent(fn (Request $request): bool => $request->method() === 'GET'
            && $request->hasHeader('x-api-key', 'tk_plain'));
    }

    #[Test]
    public function creating_sends_the_type_in_the_shape_tickets_takes(): void
    {
        $this->connect();
        $this->actingAsUserWith(PermissionEnum::TICKETING_MANAGE->value);

        Http::fake([
            self::TICKETS_URL . '/api/integration/v1/pass-types' => Http::response(['id' => self::PASS_TYPE_ID]),
        ]);

        $this->post(route('settings.tickets.passes.store'), $this->card())
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        Http::assertSent(fn (Request $request): bool => $request->method() === 'POST'
            && $request['price'] === 9000
            && $request['saleEnds'] === '2027-06-30'
            && $request['eventIds'] === [self::PRODUCTION_ID]
            && $request['maxUsesPerPeriod'] === 1
            && $request['limitPeriod'] === 'week'
            && $request['description'] === null);
    }

    #[Test]
    public function a_pass_can_count_for_a_group(): void
    {
        $this->connect();
        $this->actingAsUserWith(PermissionEnum::TICKETING_MANAGE->value);

        Http::fake([
            self::TICKETS_URL . '/api/integration/v1/pass-types' => Http::response(['id' => self::PASS_TYPE_ID]),
        ]);

        $this->post(route('settings.tickets.passes.store'), $this->card(['eventIds' => [], 'groupIds' => [self::GROUP_ID]]))
            ->assertSessionHasNoErrors();

        Http::assertSent(fn (Request $request): bool => $request['groupIds'] === [self::GROUP_ID]
            && $request['eventIds'] === []);
    }

    #[Test]
    public function a_subscription_without_a_price_never_reaches_tickets(): void
    {
        $this->connect();
        $this->actingAsUserWith(PermissionEnum::TICKETING_MANAGE->value);
        Http::fake();

        $this->post(route('settings.tickets.passes.store'), $this->card([
            'kind' => 'subscription',
            'price' => null,
            'termMode' => 'open',
            'validityDays' => null,
        ]))->assertSessionHasErrors('monthlyPrice');

        Http::assertNothingSent();
    }

    #[Test]
    public function a_pace_limit_needs_a_known_period(): void
    {
        $this->connect();
        $this->actingAsUserWith(PermissionEnum::TICKETING_MANAGE->value);
        Http::fake();

        $this->post(route('settings.tickets.passes.store'), $this->card(['limitPeriod' => 'fortnight']))
            ->assertSessionHasErrors('limitPeriod');

        Http::assertNothingSent();
    }

    #[Test]
    public function a_refusal_from_tickets_comes_back_on_the_form(): void
    {
        $this->connect();
        $this->actingAsUserWith(PermissionEnum::TICKETING_MANAGE->value);

        Http::fake([
            self::TICKETS_URL . '/api/integration/v1/pass-types/' . self::PASS_TYPE_ID => Http::response(
                ['error' => ['code' => 'PASS_TYPE_SOLD', 'message' => 'PASS_TYPE_SOLD']],
                409,
            ),
        ]);

        $this->put(route('settings.tickets.passes.update', self::PASS_TYPE_ID), $this->card(['uses' => 12]))
            ->assertSessionHasErrors('tickets');

        Http::assertSent(fn (Request $request): bool => $request->method() === 'PUT' && $request['uses'] === 12);
    }

    #[Test]
    public function publish_archive_and_delete_reach_their_endpoints(): void
    {
        $this->connect();
        $this->actingAsUserWith(PermissionEnum::TICKETING_MANAGE->value);
        $base = self::TICKETS_URL . '/api/integration/v1/pass-types/' . self::PASS_TYPE_ID;

        Http::fake([
            $base . '/publish' => Http::response(['status' => 'published']),
            $base . '/archive' => Http::response(['status' => 'archived']),
            $base => Http::response(['deleted' => true]),
        ]);

        $this->post(route('settings.tickets.passes.publish', self::PASS_TYPE_ID))->assertSessionHas('success');
        $this->post(route('settings.tickets.passes.archive', self::PASS_TYPE_ID))->assertSessionHas('success');
        $this->delete(route('settings.tickets.passes.destroy', self::PASS_TYPE_ID))->assertSessionHas('success');

        Http::assertSent(fn (Request $request): bool => $request->method() === 'POST' && $request->url() === $base . '/publish');
        Http::assertSent(fn (Request $request): bool => $request->method() === 'POST' && $request->url() === $base . '/archive');
        Http::assertSent(fn (Request $request): bool => $request->method() === 'DELETE' && $request->url() === $base);
    }

    #[Test]
    public function the_tab_needs_the_ticketing_permission(): void
    {
        $this->connect();
        $this->actingAs(User::factory()->create());

        $this->get(route('settings.tickets.passes'))->assertForbidden();
        $this->post(route('settings.tickets.passes.store'), $this->card())->assertForbidden();
    }
}
