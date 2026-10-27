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
 * Tab "Produktionsgruppen": Gruppen von Produktionen, für die Karten, Abos und Rabattcodes in
 * Artwork-Tickets als Ganzes gelten — tickets hält sie, hier wird gepflegt.
 */
final class TicketingProductionGroupTest extends FeatureTestCase
{
    use ActsAsRole;

    private const TICKETS_URL = 'https://tickets.test';

    private const GROUP_ID = '7a1e2b3c-4d5e-4f60-9a1b-2c3d4e5f6a7b';

    private const PRODUCTION_ID = '5d2c7a10-1b2c-4d3e-8f90-a1b2c3d4e5f6';

    private const BASE = self::TICKETS_URL . '/api/integration/v1/production-groups';

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
            'organization_slug' => 'theater-sued',
            'dashboard_url' => self::TICKETS_URL . '/dashboard',
            'api_key' => 'tk_plain',
            'oauth_client_id' => '1',
            'connected_by_user_id' => User::factory()->create()->id,
        ]);
    }

    #[Test]
    public function the_tab_shows_the_groups_and_productions_from_tickets(): void
    {
        $this->connect();
        $this->actingAsUserWith(PermissionEnum::TICKETING_MANAGE->value);

        Http::fake([
            self::BASE => Http::response([
                'groups' => [[
                    'id' => self::GROUP_ID,
                    'name' => 'Workshops',
                    'eventIds' => [self::PRODUCTION_ID],
                    'passTypeCount' => 2,
                    'couponCount' => 1,
                    'sold' => true,
                    'deletable' => false,
                ]],
                'productions' => [['id' => self::PRODUCTION_ID, 'title' => 'Faust']],
            ]),
        ]);

        $this->get(route('settings.tickets.groups'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Settings/Tickets/Groups')
                ->where('connection.connected', true)
                ->where('groups.0.name', 'Workshops')
                ->where('groups.0.couponCount', 1)
                ->where('groups.0.deletable', false)
                ->where('productions.0.title', 'Faust')
                ->where('ticketsError', null));

        Http::assertSent(fn (Request $request): bool => $request->method() === 'GET'
            && $request->hasHeader('x-api-key', 'tk_plain'));
    }

    #[Test]
    public function a_group_goes_to_tickets_with_its_productions(): void
    {
        $this->connect();
        $this->actingAsUserWith(PermissionEnum::TICKETING_MANAGE->value);

        Http::fake([
            self::BASE => Http::response(['id' => self::GROUP_ID]),
            self::BASE . '/' . self::GROUP_ID => Http::response(['id' => self::GROUP_ID]),
        ]);

        $this->post(route('settings.tickets.groups.store'), ['name' => 'Workshops', 'eventIds' => [self::PRODUCTION_ID]])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');
        $this->put(route('settings.tickets.groups.update', self::GROUP_ID), ['name' => 'Werkstatt', 'eventIds' => []])
            ->assertSessionHasNoErrors();

        Http::assertSent(fn (Request $request): bool => $request->method() === 'POST'
            && $request->url() === self::BASE
            && $request['name'] === 'Workshops'
            && $request['eventIds'] === [self::PRODUCTION_ID]);
        Http::assertSent(fn (Request $request): bool => $request->method() === 'PUT'
            && $request->url() === self::BASE . '/' . self::GROUP_ID
            && $request['name'] === 'Werkstatt');
    }

    #[Test]
    public function a_group_needs_a_name(): void
    {
        $this->connect();
        $this->actingAsUserWith(PermissionEnum::TICKETING_MANAGE->value);
        Http::fake();

        $this->post(route('settings.tickets.groups.store'), ['name' => ' ', 'eventIds' => []])
            ->assertSessionHasErrors('name');

        Http::assertNothingSent();
    }

    #[Test]
    public function a_group_in_use_refused_by_tickets_comes_back_on_the_form(): void
    {
        $this->connect();
        $this->actingAsUserWith(PermissionEnum::TICKETING_MANAGE->value);
        $url = self::BASE . '/' . self::GROUP_ID;

        Http::fake([
            $url => Http::response(['error' => ['code' => 'GROUP_IN_USE', 'message' => 'GROUP_IN_USE']], 409),
        ]);

        $this->put(route('settings.tickets.groups.update', self::GROUP_ID), ['name' => 'Workshops', 'eventIds' => []])
            ->assertSessionHasErrors('tickets');
        $this->delete(route('settings.tickets.groups.destroy', self::GROUP_ID))
            ->assertSessionHas('error');

        Http::assertSent(fn (Request $request): bool => $request->method() === 'DELETE' && $request->url() === $url);
    }

    #[Test]
    public function the_tab_needs_the_ticketing_permission(): void
    {
        $this->connect();
        $this->actingAs(User::factory()->create());

        $this->get(route('settings.tickets.groups'))->assertForbidden();
        $this->post(route('settings.tickets.groups.store'), ['name' => 'Workshops', 'eventIds' => []])->assertForbidden();
    }
}
