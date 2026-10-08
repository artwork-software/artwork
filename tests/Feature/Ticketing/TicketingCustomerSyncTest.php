<?php

namespace Tests\Feature\Ticketing;

use Artwork\Modules\Crm\Enums\CrmPropertyTypeEnum;
use Artwork\Modules\Crm\Enums\CrmSystemContactTypeEnum;
use Artwork\Modules\Crm\Models\CrmContact;
use Artwork\Modules\Crm\Models\CrmContactType;
use Artwork\Modules\Crm\Models\CrmProperty;
use Artwork\Modules\Crm\Models\CrmPropertyGroup;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Ticketing\Jobs\SyncTicketingCustomersJob;
use Artwork\Modules\Ticketing\Models\TicketingConnection;
use Artwork\Modules\Ticketing\Models\TicketingCustomer;
use Artwork\Modules\User\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\ActsAsRole;
use Tests\Feature\FeatureTestCase;

/**
 * CRM-Typ "Ticketing-Kunde": die Käufer aus Artwork-Tickets, nachts und auf Knopfdruck gespiegelt.
 */
final class TicketingCustomerSyncTest extends FeatureTestCase
{
    use ActsAsRole;

    private const TICKETS_URL = 'https://tickets.test';
    private const CUSTOMERS_URL = self::TICKETS_URL . '/api/integration/v1/customers';

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.tickets.url', self::TICKETS_URL);
        config()->set('app.timezone', 'Europe/Berlin');

        $base = CrmPropertyGroup::query()->create(['name' => 'Basiseigenschaften', 'is_system' => true, 'sort_order' => 1]);
        foreach (['Vorname', 'Nachname', 'Email', 'Telefon'] as $index => $name) {
            CrmProperty::query()->create([
                'crm_property_group_id' => $base->id,
                'name' => $name,
                'type' => CrmPropertyTypeEnum::TEXT->value,
                'is_system' => true,
                'sort_order' => $index,
            ]);
        }
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
    private function customer(string $id, array $overrides = []): array
    {
        return array_merge([
            'id' => $id,
            'email' => $id . '@example.org',
            'firstName' => 'Erika',
            'lastName' => 'Muster',
            'phone' => null,
            'revenueCents' => 23850,
            'orderCount' => 3,
            'ticketCount' => 7,
            'firstPurchaseAt' => '2026-08-17T09:16:10.061Z',
            'lastPurchaseAt' => '2026-10-05T22:30:00.000Z',
        ], $overrides);
    }

    private function runSync(): void
    {
        app()->call([new SyncTicketingCustomersJob(), 'handle']);
    }

    /** @return array<string, string|null> */
    private function valuesOf(CrmContact $contact): array
    {
        return $contact->propertyValues()->with('property')->get()
            ->mapWithKeys(fn ($value) => [$value->property->name => $value->value])
            ->all();
    }

    #[Test]
    public function the_first_sync_mirrors_every_buyer_page_by_page(): void
    {
        $connection = $this->connect();

        Http::fake([
            self::CUSTOMERS_URL . '*' => Http::sequence()
                ->push(['customers' => [$this->customer('a')], 'nextCursor' => 'a'])
                ->push(['customers' => [$this->customer('b', ['firstName' => null, 'lastName' => null])], 'nextCursor' => null]),
        ]);

        Carbon::setTestNow('2026-10-06 02:30:00');
        $this->runSync();

        $type = CrmContactType::query()->where('slug', CrmSystemContactTypeEnum::TICKETING->value)->sole();
        $this->assertTrue($type->is_system);
        $this->assertSame(
            ['Vorname', 'Nachname', 'Email', 'Telefon', 'Umsatz (€)', 'Bestellungen', 'Tickets', 'Erster Kauf', 'Letzter Kauf'],
            $type->properties->pluck('name')->all()
        );
        $this->assertSame(['Umsatz (€)'], $type->properties->where('pivot.is_filterable', true)->pluck('name')->all());

        $erika = TicketingCustomer::query()->where('customer_id', 'a')->sole()->crmContact;
        $this->assertSame('Erika Muster', $erika->display_name);
        $this->assertSame([
            'Vorname' => 'Erika',
            'Nachname' => 'Muster',
            'Email' => 'a@example.org',
            'Telefon' => null,
            'Umsatz (€)' => '238.50',
            'Bestellungen' => '3',
            'Tickets' => '7',
            'Erster Kauf' => '2026-08-17',
            // 22:30 UTC ist in Berlin schon der nächste Tag
            'Letzter Kauf' => '2026-10-06',
        ], $this->valuesOf($erika));

        $this->assertSame('b@example.org', TicketingCustomer::query()->where('customer_id', 'b')->sole()->crmContact->display_name);

        Http::assertSent(fn (Request $request) => $request->url() === self::CUSTOMERS_URL . '?limit=200'
            && $request->header('x-api-key') === ['tk_plain']);
        Http::assertSent(fn (Request $request) => $request->url() === self::CUSTOMERS_URL . '?after=a&limit=200');

        $connection->refresh();
        $this->assertTrue($connection->customers_synced_at->equalTo(Carbon::parse('2026-10-06 02:30:00')));
        $this->assertNull($connection->customers_sync_started_at);
    }

    #[Test]
    public function a_type_created_by_hand_with_the_slug_becomes_the_system_type(): void
    {
        $this->connect();
        $manual = CrmContactType::query()->create([
            'name' => 'Ticketing',
            'slug' => CrmSystemContactTypeEnum::TICKETING->value,
            'is_system' => false,
            'is_active' => true,
            'sort_order' => 7,
        ]);
        Http::fake([self::CUSTOMERS_URL . '*' => Http::response(['customers' => [], 'nextCursor' => null])]);

        $this->runSync();

        $type = CrmContactType::query()->where('slug', CrmSystemContactTypeEnum::TICKETING->value)->sole();
        $this->assertSame($manual->id, $type->id);
        $this->assertTrue($type->is_system);
        $this->assertSame('Ticketing', $type->name);
        $this->assertSame('IconTicket', $type->icon);
    }

    #[Test]
    public function a_later_sync_asks_only_for_changes_and_overwrites_the_contact(): void
    {
        $connection = $this->connect();
        Http::fake([self::CUSTOMERS_URL . '*' => Http::sequence()
            ->push(['customers' => [$this->customer('a')], 'nextCursor' => null])
            ->push(['customers' => [$this->customer('a', ['revenueCents' => 30000, 'orderCount' => 4])], 'nextCursor' => null]),
        ]);

        Carbon::setTestNow('2026-10-05 02:30:00');
        $this->runSync();
        Carbon::setTestNow('2026-10-06 02:30:00');
        $this->runSync();

        $this->assertSame(1, TicketingCustomer::query()->count());
        $values = $this->valuesOf(TicketingCustomer::query()->sole()->crmContact);
        $this->assertSame('300.00', $values['Umsatz (€)']);
        $this->assertSame('4', $values['Bestellungen']);

        // Beginn des letzten Laufs, zehn Minuten Überlappung gegen Uhrenabweichung
        $since = Carbon::parse('2026-10-05 02:20:00')->toIso8601String();
        Http::assertSent(fn (Request $request) => $request->url() === self::CUSTOMERS_URL . '?' . http_build_query(['changedSince' => $since, 'limit' => 200]));
        $this->assertTrue($connection->refresh()->customers_synced_at->equalTo(Carbon::parse('2026-10-06 02:30:00')));
    }

    #[Test]
    public function a_run_stopped_by_the_rate_limit_resumes_at_its_page(): void
    {
        $connection = $this->connect();
        Http::fake([self::CUSTOMERS_URL . '*' => Http::sequence()
            ->push(['customers' => [$this->customer('a')], 'nextCursor' => 'a'])
            ->push(['error' => ['code' => 'RATE_LIMITED', 'message' => 'Too many requests.']], 429)
            ->push(['customers' => [$this->customer('b')], 'nextCursor' => null]),
        ]);

        Carbon::setTestNow('2026-10-06 02:30:00');
        $this->runSync();

        $connection->refresh();
        $this->assertSame('a', $connection->customers_sync_cursor);
        $this->assertNotNull($connection->customers_sync_error);
        $this->assertNull($connection->customers_synced_at);

        Carbon::setTestNow('2026-10-06 02:31:00');
        $this->runSync();

        Http::assertSentCount(3);
        Http::assertSent(fn (Request $request) => $request->url() === self::CUSTOMERS_URL . '?after=a&limit=200');
        $this->assertSame(2, TicketingCustomer::query()->count());

        $connection->refresh();
        $this->assertNull($connection->customers_sync_error);
        // Der Lauf zählt ab seinem ersten Start — was dazwischen geschah, fragt der nächste ab
        $this->assertTrue($connection->customers_synced_at->equalTo(Carbon::parse('2026-10-06 02:30:00')));
    }

    #[Test]
    public function the_button_starts_a_sync_for_crm_managers_only(): void
    {
        $this->connect();

        $this->actingAsUserWith(PermissionEnum::CRM_VIEW->value);
        $this->post(route('crm.ticketing-customers.sync'))->assertForbidden();
        Bus::assertNotDispatched(SyncTicketingCustomersJob::class);

        $this->actingAsUserWith([PermissionEnum::CRM_VIEW->value, PermissionEnum::CRM_MANAGER->value]);
        $this->post(route('crm.ticketing-customers.sync'))->assertRedirect();
        Bus::assertDispatched(SyncTicketingCustomersJob::class);
    }

    #[Test]
    public function the_contact_page_reads_the_orders_from_tickets(): void
    {
        $this->connect();
        Http::fake([
            self::CUSTOMERS_URL . '?*' => Http::response(['customers' => [$this->customer('a')], 'nextCursor' => null]),
            self::CUSTOMERS_URL . '/a' => Http::response(['customer' => ['id' => 'a', 'orders' => [], 'passes' => [], 'orderTotal' => 0]]),
        ]);
        $this->runSync();
        $contact = TicketingCustomer::query()->sole()->crmContact;
        $other = CrmContact::factory()->create();

        $this->actingAsUserWith(PermissionEnum::CRM_VIEW->value);

        $this->getJson(route('crm.contacts.ticketing', $contact))
            ->assertOk()
            ->assertJsonPath('customer.id', 'a');
        $this->getJson(route('crm.contacts.ticketing', $other))->assertNotFound();
    }

    #[Test]
    public function mirrored_buyers_cannot_be_edited_in_the_crm(): void
    {
        $this->connect();
        Http::fake([self::CUSTOMERS_URL . '*' => Http::response(['customers' => [$this->customer('a')], 'nextCursor' => null])]);
        $this->runSync();
        $contact = TicketingCustomer::query()->sole()->crmContact;

        $this->actingAsUserWith([PermissionEnum::CRM_VIEW->value, PermissionEnum::CRM_MANAGER->value]);

        $this->patch(route('crm.contacts.update', $contact), ['display_name' => 'Jemand anderes'])->assertForbidden();
        $this->delete(route('crm.contacts.destroy', $contact))->assertForbidden();
    }
}
