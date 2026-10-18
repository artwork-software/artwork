<?php

namespace Artwork\Modules\Ticketing\Services;

use Artwork\Modules\Crm\Enums\CrmPropertyTypeEnum;
use Artwork\Modules\Crm\Enums\CrmSystemContactTypeEnum;
use Artwork\Modules\Crm\Models\CrmContact;
use Artwork\Modules\Crm\Models\CrmContactType;
use Artwork\Modules\Crm\Models\CrmProperty;
use Artwork\Modules\Crm\Models\CrmPropertyGroup;
use Artwork\Modules\Crm\Services\CrmContactService;
use Artwork\Modules\Ticketing\Models\TicketingConnection;
use Artwork\Modules\Ticketing\Models\TicketingCustomer;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Spiegelt die Käufer aus Artwork-Tickets als CRM-Kontakte vom Typ "Ticketing-Kunde". tickets liefert
 * je Person den ganzen Stand (Umsatz, Bestellungen, …), der Abgleich überschreibt also nur. Nach dem
 * ersten Lauf kommen nur Personen, deren Bestellungen sich seit dem letzten vollständigen Lauf bewegt haben.
 */
readonly class TicketingCustomerSyncService
{
    private const GROUP_NAME = 'Artwork-Tickets';

    private const PAGE_SIZE = 200;

    /** Überlappung gegen Uhrenabweichung zwischen hier und tickets; doppelt Abgeglichenes schadet nicht. */
    private const CHANGED_SINCE_MARGIN_MINUTES = 10;

    private const PROPERTY_REVENUE = 'Umsatz (€)';
    private const PROPERTY_ORDERS = 'Bestellungen';
    private const PROPERTY_TICKETS = 'Tickets';
    private const PROPERTY_FIRST_PURCHASE = 'Erster Kauf';
    private const PROPERTY_LAST_PURCHASE = 'Letzter Kauf';

    /** Felder aus den Basiseigenschaften, die der Abgleich befüllt. */
    private const BASE_PROPERTIES = ['Vorname', 'Nachname', 'Email', 'Telefon'];

    public function __construct(
        private TicketsClient $tickets,
        private CrmContactService $contacts,
    ) {
    }

    /**
     * Legt Typ, Eigenschaftsgruppe und Eigenschaften an, soweit es sie noch nicht gibt.
     * Die Basiseigenschaften legt die CRM-Migration an; fehlen sie, bleiben sie hier weg.
     */
    public function ensureContactType(): CrmContactType
    {
        $type = CrmContactType::withTrashed()->firstOrCreate(
            ['slug' => CrmSystemContactTypeEnum::TICKETING->value],
            [
                'name' => 'Ticketing-Kunde',
                'icon' => 'IconTicket',
                'is_system' => true,
                'is_active' => true,
                'sort_order' => (int) CrmContactType::query()->max('sort_order') + 1,
            ]
        );

        // Ein vorher von Hand angelegter Typ mit diesem Slug wird übernommen: ab jetzt System-Typ,
        // Name und Farbe bleiben, wie das Haus sie gewählt hat.
        if ($type->trashed()) {
            $type->restore();
        }
        if (!$type->is_system || !$type->is_active) {
            $type->update(['is_system' => true, 'is_active' => true, 'icon' => $type->icon ?: 'IconTicket']);
        }

        $group = CrmPropertyGroup::query()->firstOrCreate(
            ['name' => self::GROUP_NAME, 'is_system' => true],
            ['sort_order' => (int) CrmPropertyGroup::query()->max('sort_order') + 1]
        );

        $ticketProperties = [
            self::PROPERTY_REVENUE => CrmPropertyTypeEnum::NUMBER,
            self::PROPERTY_ORDERS => CrmPropertyTypeEnum::NUMBER,
            self::PROPERTY_TICKETS => CrmPropertyTypeEnum::NUMBER,
            self::PROPERTY_FIRST_PURCHASE => CrmPropertyTypeEnum::DATE,
            self::PROPERTY_LAST_PURCHASE => CrmPropertyTypeEnum::DATE,
        ];

        $properties = CrmProperty::query()
            ->whereIn('name', self::BASE_PROPERTIES)
            ->whereHas('group', fn ($query) => $query->where('name', 'Basiseigenschaften')->where('is_system', true))
            ->get()
            ->sortBy(fn (CrmProperty $property) => array_search($property->name, self::BASE_PROPERTIES, true))
            ->values();

        $sortOrder = 0;
        foreach ($ticketProperties as $name => $propertyType) {
            $properties->push(CrmProperty::query()->firstOrCreate(
                ['name' => $name, 'crm_property_group_id' => $group->id],
                ['type' => $propertyType->value, 'is_system' => true, 'sort_order' => $sortOrder++]
            ));
        }

        // Nur Fehlendes zuweisen: Reihenfolge und Listenspalten, die jemand in den CRM-Einstellungen
        // geändert hat, bleiben stehen.
        $assignedIds = $type->properties()->pluck('crm_properties.id')->all();
        $listed = ['Email', self::PROPERTY_REVENUE, self::PROPERTY_LAST_PURCHASE];
        $type->properties()->attach(
            $properties
                ->reject(fn (CrmProperty $property) => in_array($property->id, $assignedIds, true))
                ->mapWithKeys(fn (CrmProperty $property, int $index) => [$property->id => [
                    'sort_order' => $index,
                    'is_required' => false,
                    'show_in_list' => in_array($property->name, $listed, true),
                    'is_filterable' => $property->name === self::PROPERTY_REVENUE,
                ]])
                ->all()
        );

        return $type->load('properties');
    }

    /**
     * Ein Lauf — oder die Fortsetzung eines abgebrochenen ab dessen letzter vollständiger Seite.
     *
     * @throws \Artwork\Modules\Ticketing\Exceptions\TicketingConnectionException
     */
    public function sync(TicketingConnection $connection): void
    {
        $type = $this->ensureContactType();
        $propertyIds = $type->properties->pluck('id', 'name');

        if ($connection->customers_sync_started_at === null) {
            $connection->update(['customers_sync_started_at' => now(), 'customers_sync_cursor' => null]);
        }

        $changedSince = $connection->customers_synced_at
            ?->copy()
            ->subMinutes(self::CHANGED_SINCE_MARGIN_MINUTES)
            ->toIso8601String();

        do {
            $page = $this->tickets->get($connection, '/customers', array_filter([
                'changedSince' => $changedSince,
                'after' => $connection->customers_sync_cursor,
                'limit' => self::PAGE_SIZE,
            ]));

            DB::transaction(function () use ($page, $type, $propertyIds): void {
                foreach ($page['customers'] as $customer) {
                    $this->mirror($customer, $type, $propertyIds);
                }
            });

            $connection->update(['customers_sync_cursor' => $page['nextCursor']]);
        } while ($page['nextCursor'] !== null);

        $connection->update([
            'customers_synced_at' => $connection->customers_sync_started_at,
            'customers_sync_started_at' => null,
            'customers_sync_error' => null,
        ]);
    }

    /**
     * @param array{
     *     id: string,
     *     email: string,
     *     firstName: string|null,
     *     lastName: string|null,
     *     phone: string|null,
     *     revenueCents: int,
     *     orderCount: int,
     *     ticketCount: int,
     *     firstPurchaseAt: string,
     *     lastPurchaseAt: string
     * } $customer
     * @param \Illuminate\Support\Collection<string, int> $propertyIds
     */
    private function mirror(array $customer, CrmContactType $type, \Illuminate\Support\Collection $propertyIds): void
    {
        $displayName = trim(($customer['firstName'] ?? '') . ' ' . ($customer['lastName'] ?? ''));
        $data = ['crm_contact_type_id' => $type->id, 'display_name' => $displayName ?: $customer['email']];

        $values = collect([
            'Vorname' => $customer['firstName'],
            'Nachname' => $customer['lastName'],
            'Email' => $customer['email'],
            'Telefon' => $customer['phone'],
            self::PROPERTY_REVENUE => number_format($customer['revenueCents'] / 100, 2, '.', ''),
            self::PROPERTY_ORDERS => (string) $customer['orderCount'],
            self::PROPERTY_TICKETS => (string) $customer['ticketCount'],
            self::PROPERTY_FIRST_PURCHASE => $this->day($customer['firstPurchaseAt']),
            self::PROPERTY_LAST_PURCHASE => $this->day($customer['lastPurchaseAt']),
        ])
            ->only($propertyIds->keys()->all())
            ->mapWithKeys(fn (?string $value, string $name) => [$propertyIds[$name] => $value])
            ->all();

        $contact = CrmContact::withTrashed()
            ->whereIn('id', TicketingCustomer::query()->where('customer_id', $customer['id'])->select('crm_contact_id'))
            ->first();

        if ($contact) {
            $this->contacts->update($contact, $data, $values);

            return;
        }

        $contact = $this->contacts->store($data, $values);
        TicketingCustomer::query()->create(['customer_id' => $customer['id'], 'crm_contact_id' => $contact->id]);
    }

    /** Kalendertag in der Zeitzone dieser Instanz — so, wie ein Datumsfeld im CRM ihn speichert. */
    private function day(string $timestamp): string
    {
        return Carbon::parse($timestamp)->setTimezone(config('app.timezone'))->toDateString();
    }
}
