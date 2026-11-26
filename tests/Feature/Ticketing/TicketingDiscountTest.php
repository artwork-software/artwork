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

/** Tab "Rabatte & Pakete": Rabatte, die der Shop in Artwork-Tickets von selbst anwendet. */
final class TicketingDiscountTest extends FeatureTestCase
{
    use ActsAsRole;

    private const TICKETS_URL = 'https://tickets.test';

    private const DISCOUNT_ID = '3c4d5e6f-7a8b-4c9d-8e0f-1a2b3c4d5e6f';

    private const GROUP_ID = '7a1e2b3c-4d5e-4f60-9a1b-2c3d4e5f6a7b';

    private const BASE = self::TICKETS_URL . '/api/integration/v1/discounts';

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

    /** @return array<string, mixed> */
    private function earlyBird(array $overrides = []): array
    {
        return $overrides + [
            'name' => 'Frühbucher',
            'kind' => 'percent',
            'value' => 1500,
            'allEvents' => false,
            'eventIds' => [],
            'groupIds' => [self::GROUP_ID],
            'validFrom' => null,
            'validUntil' => '2027-04-01',
            'status' => 'active',
        ];
    }

    #[Test]
    public function the_tab_shows_the_discounts_from_tickets(): void
    {
        $this->connect();
        $this->actingAsUserWith(PermissionEnum::TICKETING_MANAGE->value);
        Http::fake([self::BASE => Http::response([
            'discounts' => [['id' => self::DISCOUNT_ID] + $this->earlyBird()],
            'productions' => [],
            'groups' => [['id' => self::GROUP_ID, 'name' => 'Workshops', 'eventIds' => []]],
        ])]);

        $this->get(route('settings.tickets.discounts'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Settings/Tickets/Discounts')
                ->where('discounts.0.name', 'Frühbucher')
                ->where('groups.0.name', 'Workshops')
                ->where('ticketsError', null));
    }

    #[Test]
    public function a_discount_goes_to_tickets_and_back(): void
    {
        $this->connect();
        $this->actingAsUserWith(PermissionEnum::TICKETING_MANAGE->value);
        Http::fake([
            self::BASE => Http::response(['id' => self::DISCOUNT_ID]),
            self::BASE . '/' . self::DISCOUNT_ID => Http::response(['id' => self::DISCOUNT_ID]),
        ]);

        $this->post(route('settings.tickets.discounts.store'), $this->earlyBird())->assertSessionHasNoErrors();
        $this->put(route('settings.tickets.discounts.update', self::DISCOUNT_ID), $this->earlyBird(['status' => 'paused']))
            ->assertSessionHasNoErrors();
        $this->delete(route('settings.tickets.discounts.destroy', self::DISCOUNT_ID))->assertSessionHas('success');

        Http::assertSent(fn (Request $request): bool => $request->method() === 'POST'
            && $request->url() === self::BASE
            && $request['value'] === 1500
            && $request['groupIds'] === [self::GROUP_ID]
            && $request['validUntil'] === '2027-04-01');
        Http::assertSent(fn (Request $request): bool => $request->method() === 'PUT' && $request['status'] === 'paused');
        Http::assertSent(fn (Request $request): bool => $request->method() === 'DELETE'
            && $request->url() === self::BASE . '/' . self::DISCOUNT_ID);
    }

    #[Test]
    public function a_discount_needs_a_scope_and_a_sane_value(): void
    {
        $this->connect();
        $this->actingAsUserWith(PermissionEnum::TICKETING_MANAGE->value);
        Http::fake();

        $this->post(route('settings.tickets.discounts.store'), $this->earlyBird(['groupIds' => []]))
            ->assertSessionHasErrors('eventIds');
        $this->post(route('settings.tickets.discounts.store'), $this->earlyBird(['value' => 12000]))
            ->assertSessionHasErrors('value');
        $this->post(route('settings.tickets.discounts.store'), $this->earlyBird(['kind' => 'package', 'value' => 6000]))
            ->assertSessionHasErrors('minDates');

        Http::assertNothingSent();
    }

    #[Test]
    public function a_package_travels_with_its_size(): void
    {
        $this->connect();
        $this->actingAsUserWith(PermissionEnum::TICKETING_MANAGE->value);
        Http::fake([self::BASE => Http::response(['id' => self::DISCOUNT_ID])]);

        $package = $this->earlyBird(['kind' => 'package', 'value' => 6000, 'minDates' => 3]);

        $this->post(route('settings.tickets.discounts.store'), $package)->assertSessionHasNoErrors();

        Http::assertSent(fn (Request $request): bool => $request['kind'] === 'package'
            && $request['value'] === 6000
            && $request['minDates'] === 3);
    }

    #[Test]
    public function the_project_tab_opens_a_package_for_its_production(): void
    {
        $this->connect();
        $this->actingAsUserWith(PermissionEnum::TICKETING_MANAGE->value);
        Http::fake([self::BASE => Http::response(['discounts' => [], 'productions' => [], 'groups' => []])]);

        $this->get(route('settings.tickets.discounts', ['package' => self::DISCOUNT_ID]))
            ->assertInertia(fn ($page) => $page->where('packageFor', self::DISCOUNT_ID));
    }

    #[Test]
    public function the_tab_needs_the_ticketing_permission(): void
    {
        $this->connect();
        $this->actingAs(User::factory()->create());

        $this->get(route('settings.tickets.discounts'))->assertForbidden();
        $this->post(route('settings.tickets.discounts.store'), $this->earlyBird())->assertForbidden();
    }
}
