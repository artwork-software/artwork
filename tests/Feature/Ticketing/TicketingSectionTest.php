<?php

namespace Tests\Feature\Ticketing;

use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Ticketing\Models\TicketingConnection;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\ActsAsRole;
use Tests\Feature\FeatureTestCase;

/** Hauptmenü → Artwork-Tickets: Verkäufe sowie Karten und Abos, gelesen aus tickets. */
final class TicketingSectionTest extends FeatureTestCase
{
    use ActsAsRole;

    private const TICKETS_URL = 'https://tickets.test';
    private const BASE = self::TICKETS_URL . '/api/integration/v1';

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
    public function the_sales_page_shows_the_period_from_tickets(): void
    {
        $this->connect();
        $this->actingAsUserWith(PermissionEnum::TICKETING_VIEW_SALES->value);
        Http::fake([self::BASE . '/sales*' => Http::response(['hasEverSold' => true, 'current' => ['revenueCents' => 5800], 'orders' => []])]);

        $this->get(route('ticketing.section.sales', ['days' => 7]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Ticketing/Sales')
                ->where('connected', true)
                ->where('sales.current.revenueCents', 5800)
                ->where('filters.days', '7'));

        Http::assertSent(fn (Request $request): bool => str_starts_with($request->url(), self::BASE . '/sales')
            && $request['days'] === '7');
    }

    #[Test]
    public function several_productions_and_a_custom_range_are_passed_on(): void
    {
        $this->connect();
        $this->actingAsUserWith(PermissionEnum::TICKETING_VIEW_SALES->value);
        Http::fake([self::BASE . '/sales*' => Http::response(['hasEverSold' => true, 'orders' => []])]);
        $first = (string) Str::uuid();
        $second = (string) Str::uuid();

        $this->get(route('ticketing.section.sales', ['from' => '2026-09-01', 'to' => '2026-09-30', 'events' => [$first, $second]]))
            ->assertOk();

        Http::assertSent(fn (Request $request): bool => str_starts_with($request->url(), self::BASE . '/sales')
            && $request['from'] === '2026-09-01'
            && $request['to'] === '2026-09-30'
            && $request['events'] === "{$first},{$second}");
    }

    #[Test]
    public function a_range_needs_both_ends_in_order(): void
    {
        $this->connect();
        $this->actingAsUserWith(PermissionEnum::TICKETING_VIEW_SALES->value);
        Http::fake();

        $this->get(route('ticketing.section.sales', ['from' => '2026-09-30', 'to' => '2026-09-01']))->assertSessionHasErrors('from');
        $this->get(route('ticketing.section.sales', ['from' => '2026-09-01']))->assertSessionHasErrors('to');
        Http::assertNothingSent();
    }

    #[Test]
    public function passes_and_subscriptions_are_looked_up_in_tickets(): void
    {
        $this->connect();
        $this->actingAsUserWith(PermissionEnum::TICKETING_VIEW_SALES->value);
        Http::fake([
            self::BASE . '/passes*' => Http::response(['passes' => [['id' => 'p1', 'name' => '10er-Karte']]]),
            self::BASE . '/subscriptions*' => Http::response(['subscriptions' => [['id' => 's1', 'planName' => 'Jahresabo']]]),
        ]);

        $this->get(route('ticketing.section.passes', ['q' => 'Ada']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Ticketing/Passes')->where('passes.0.name', '10er-Karte'));
        $this->get(route('ticketing.section.passes', ['tab' => 'subscriptions', 'state' => 'pastDue']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('subscriptions.0.planName', 'Jahresabo'));

        Http::assertSent(fn (Request $request): bool => str_starts_with($request->url(), self::BASE . '/passes') && $request['q'] === 'Ada');
        Http::assertSent(fn (Request $request): bool => str_starts_with($request->url(), self::BASE . '/subscriptions')
            && $request['state'] === 'pastDue');
    }

    #[Test]
    public function without_a_connection_the_page_says_so(): void
    {
        $this->actingAsUserWith(PermissionEnum::TICKETING_VIEW_SALES->value);
        Http::fake();

        $this->get(route('ticketing.section.sales'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('connected', false)->where('sales', null));

        Http::assertNothingSent();
    }

    #[Test]
    public function the_section_needs_its_permission(): void
    {
        $this->connect();
        $this->actingAsUserWith([]);

        $this->get(route('ticketing.section.sales'))->assertForbidden();
        $this->get(route('ticketing.section.passes'))->assertForbidden();
    }
}
