<?php

namespace Artwork\Modules\Ticketing\Services;

use Artwork\Modules\GeneralSettings\Models\GeneralSettings;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\Ticketing\Exceptions\TicketingConnectionException;
use Artwork\Modules\Ticketing\Jobs\SyncTicketingCustomersJob;
use Artwork\Modules\Ticketing\Models\TicketingConnection;
use Artwork\Modules\Ticketing\Models\TicketingEventRelease;
use Artwork\Modules\Ticketing\Models\TicketingProduction;
use Artwork\Modules\Ticketing\Models\TicketingProductionImage;
use Artwork\Modules\Ticketing\Models\TicketingRoomLink;
use Artwork\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Laravel\Passport\Client;
use Laravel\Passport\ClientRepository;

/**
 * Handshake mit Artwork-Tickets: diese Instanz legt einen OAuth-Client (client_credentials) an,
 * tickets legt dafür ein Haus mit dem auslösenden Account als Inhaber*in an und gibt seinen
 * eigenen Schlüssel zurück. Danach halten beide Seiten eine Zugangsberechtigung für die andere,
 * die keiner Person gehört: tickets holt sich mit dem Client Tokens, deren Scopes
 * (ticketing:*) entscheiden, was es hier darf.
 *
 * Räume und Ermäßigungen folgen nach dem Handshake über den Hausschlüssel — dieselben Aufrufe,
 * die später auch ein erneuter Abgleich nutzt.
 */
class TicketingConnectionService
{
    public const CLIENT_NAME = 'Artwork-Tickets';

    public function __construct(
        private readonly GeneralSettings $generalSettings,
        private readonly ClientRepository $clients,
        private readonly TicketingBillingService $billing,
        private readonly TicketsClient $tickets,
    ) {
    }

    public function isConfigured(): bool
    {
        return (bool) config('services.tickets.url') && (bool) config('services.tickets.provisioning_secret');
    }

    /** Konfiguriert und verbunden: erst dann zeigt und tut artwork irgendetwas vom Ticketing. */
    public function isActive(): bool
    {
        return $this->isConfigured() && TicketingConnection::query()->exists();
    }

    public function current(): ?TicketingConnection
    {
        return TicketingConnection::query()->with('connectedBy')->first();
    }

    /**
     * Der Weg aus artwork ins Ticket-Dashboard, auf Wunsch gleich zu einem Termin, einer Käuferin
     * oder zu den Haus-Einstellungen (Shop-Darstellung): tickets gibt Mitgliedern des Hauses einen Einmal-Link,
     * allen anderen die Anmeldung mit ihrer Adresse. Ist tickets nicht erreichbar, bleibt der
     * gewöhnliche Link zum Dashboard.
     *
     * @param array{type: 'dashboard'}|array{type: 'date', dateId: string}|array{type: 'houseSettings', tab?: string}|array{type: 'customer', customerId: string} $destination
     */
    public function loginUrl(TicketingConnection $connection, User $user, array $destination): string
    {
        try {
            return (string) $this->tickets->post($connection, '/login-links', [
                'email' => $user->email,
                'destination' => $destination,
            ])['url'];
        } catch (TicketingConnectionException $exception) {
            report($exception);

            return $connection->dashboard_url;
        }
    }

    /**
     * Vorschlag für den Assistenten: Hausname aus den Allgemeinen Einstellungen, die Anschrift
     * aus dem Briefkopf — eine eigene Raumadresse kennt artwork nicht, die meisten Räume liegen
     * ohnehin im Haus.
     *
     * Vorbelegung des Assistenten aus dem, was dieses artwork über sich weiß: Firmenname und
     * Briefkopf. Was das artwork nicht kennt (Rechtsform, Steuer, Bank), bleibt leer.
     *
     * @return array{
     *     name: string,
     *     slug: string,
     *     address: array{street: string, postal_code: string, city: string, country: string},
     *     billing: array<string, string>
     * }
     */
    public function houseDefaults(User $user): array
    {
        $name = $this->generalSettings->company_name ?: (string) config('app.name');
        $address = [
            'street' => $this->generalSettings->letterhead_street,
            'postal_code' => $this->generalSettings->letterhead_zip_code,
            'city' => $this->generalSettings->letterhead_city,
            'country' => 'DE',
        ];

        return [
            'name' => $name,
            'slug' => self::slugify($name),
            'address' => $address,
            'billing' => [
                'legal_name' => $this->generalSettings->letterhead_name ?: $name,
                'legal_form' => '',
                ...$address,
                'register_number' => '',
                'register_court' => '',
                'vat_id' => '',
                'tax_number' => '',
                'contact_name' => $user->full_name,
                'contact_phone' => (string) ($user->phone_number ?? ''),
                'website' => '',
                'terms_url' => '',
                'privacy_url' => '',
                'imprint_url' => '',
            ],
        ];
    }

    /**
     * Was das Formular vor dem Verbinden wissen will: Adresse frei, Name unbenutzt, darf diese
     * Person noch ein Haus gründen. Antwort so, wie tickets sie gibt.
     *
     * @return array<string, mixed>
     */
    public function checkAvailability(string $slug, string $name, string $ownerEmail): array
    {
        $this->assertConfigured();

        return $this->tickets->provisioningGet('/availability', [
            'slug' => $slug,
            'name' => $name,
            'ownerEmail' => $ownerEmail,
        ]);
    }

    /**
     * @param array{
     *     house: array{name: string, slug: string},
     *     billing: array<string, string|UploadedFile|null>|null,
     *     rooms: list<array<string, mixed>>,
     *     reductions: list<array<string, mixed>>,
     *     accept_platform_terms: bool,
     *     test_mode: bool
     * } $draft
     */
    public function connect(User $user, array $draft): TicketingConnection
    {
        $this->assertConfigured();

        if ($this->current()) {
            throw new TicketingConnectionException(__('This installation is already connected to Artwork-Tickets.'));
        }

        $client = $this->clients->createClientCredentialsGrantClient(self::CLIENT_NAME);

        try {
            $house = $this->tickets->provisioningPost('/houses', [
                'name' => $draft['house']['name'],
                'slug' => $draft['house']['slug'],
                'ownerEmail' => $user->email,
                'ownerName' => $user->full_name,
                'billing' => $draft['billing'] === null ? null : TicketingBillingService::payload($draft['billing']),
                // Die Anfrage lässt ohne den Haken nicht durch.
                'acceptPlatformTerms' => true,
                'coreUrl' => config('app.url'),
                // Als String: je nach Installation ist der Client-Schlüssel numerisch oder eine UUID.
                'coreClientId' => (string) $client->getKey(),
                'coreClientSecret' => $client->plainSecret,
                'testMode' => $draft['test_mode'],
            ]);
        } catch (TicketingConnectionException $exception) {
            $this->clients->delete($client);

            throw $exception;
        }

        $connection = TicketingConnection::query()->create([
            'tickets_url' => config('services.tickets.url'),
            'organization_id' => $house['organizationId'],
            'organization_slug' => $house['slug'],
            'dashboard_url' => $house['dashboardUrl'],
            'api_key' => $house['apiKey'],
            'oauth_client_id' => $client->getKey(),
            'connected_by_user_id' => $user->id,
            'live_at' => $draft['test_mode'] ? null : now(),
        ]);

        // Legt den CRM-Typ "Ticketing-Kunde" an, damit es den Knopf für den Abgleich ab jetzt gibt;
        // im Testmodus erst mit dem Live-Schalten, Testkäufer gehören nicht ins CRM.
        if (!$connection->isTestMode()) {
            SyncTicketingCustomersJob::dispatch();
        }

        // Ab hier besteht die Verbindung; scheitert der Abgleich, bleibt sie und die Meldung nennt den Rest.
        try {
            $this->syncRooms($connection, $draft['rooms']);
            $this->syncReductions($connection, $draft['reductions']);
            $this->billing->uploadLegalDocuments($connection, $draft['billing'] ?? []);
        } catch (TicketingConnectionException $exception) {
            throw new TicketingConnectionException(__(
                'Connected to Artwork-Tickets, but the sync failed: :message',
                ['message' => $exception->getMessage()]
            ));
        }

        return $connection;
    }

    /**
     * Die Spielstätten, wie tickets sie gerade kennt — Ausgangspunkt für den Tab "Räume & Preisklassen".
     * Verweise auf Spielstätten, die es dort nicht mehr gibt, werden dabei vergessen.
     *
     * @return array<string, array<string, mixed>> Spielstätte je tickets-ID
     */
    public function venues(TicketingConnection $connection): array
    {
        // Ohne die Liste keine Aufräumaktion: eine fehlende Antwort darf nicht jeden Verweis löschen.
        $venues = collect($this->tickets->get($connection, '/venues')['venues']
            ?? throw new TicketingConnectionException(__('Artwork-Tickets sent an unexpected answer.')))->keyBy('id');
        TicketingRoomLink::query()->whereNotIn('venue_id', $venues->keys())->delete();

        return $venues->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function reductions(TicketingConnection $connection): array
    {
        return $this->tickets->get($connection, '/reductions')['reductions'] ?? [];
    }

    /**
     * @param list<array{
     *     id: int,
     *     street: string|null, postal_code: string|null, city: string|null, country: string,
     *     zones: list<array{name: string, capacity: int, default_price_cents: int|null}>
     * }> $rooms
     */
    public function syncRooms(TicketingConnection $connection, array $rooms): void
    {
        if ($rooms === []) {
            return;
        }

        $links = TicketingRoomLink::query()->pluck('venue_id', 'room_id');
        $names = Room::query()->whereIn('id', array_column($rooms, 'id'))->pluck('name', 'id');

        $response = $this->tickets->post($connection, '/venues', [
            'venues' => array_map(static fn (array $room): array => [
                'externalId' => (string) $room['id'],
                'venueId' => $links[$room['id']] ?? null,
                'name' => $names[$room['id']],
                'street' => $room['street'] ?? '',
                'postalCode' => $room['postal_code'] ?? '',
                'city' => $room['city'] ?? '',
                'country' => $room['country'],
                'zones' => array_map(static fn (array $zone): array => [
                    'name' => $zone['name'],
                    'capacity' => (int) $zone['capacity'],
                    'defaultPriceCents' => $zone['default_price_cents'] === null ? null : (int) $zone['default_price_cents'],
                ], $room['zones']),
            ], $rooms),
        ]);

        foreach ($response['venues'] ?? [] as $venue) {
            TicketingRoomLink::query()->updateOrCreate(
                ['room_id' => (int) $venue['externalId']],
                ['venue_id' => $venue['venueId']],
            );
        }
    }

    /**
     * @param list<array{
     *     name: string, kind: string, value: int, requires_proof: bool, default_enabled: bool
     * }> $reductions
     */
    public function syncReductions(TicketingConnection $connection, array $reductions): void
    {
        if ($reductions === []) {
            return;
        }

        $this->tickets->post($connection, '/reductions', [
            'reductions' => array_map(static fn (array $reduction): array => [
                'name' => $reduction['name'],
                'kind' => $reduction['kind'],
                'value' => (int) $reduction['value'],
                'requiresProof' => (bool) $reduction['requires_proof'],
                'defaultEnabled' => (bool) $reduction['default_enabled'],
            ], $reductions),
        ]);
    }

    /** Termine, die gerade im Shop verkauft werden: solange es welche gibt, bleibt die Verbindung. */
    public function datesOnSale(): int
    {
        return TicketingEventRelease::query()->where('state', TicketingEventRelease::STATE_RELEASED)->count();
    }

    /**
     * Ohne Verbindung erreichen Verschiebungen im Kalender den Shop nicht mehr, der aber weiter verkauft —
     * deshalb erst trennen, wenn kein Termin mehr im Verkauf ist.
     *
     * @throws TicketingConnectionException
     */
    public function disconnect(): void
    {
        $connection = $this->current();

        if (!$connection) {
            return;
        }

        $onSale = $this->datesOnSale();
        if ($onSale > 0) {
            throw new TicketingConnectionException(trans_choice(
                '{1} One date is still on sale. Withdraw it in the ticketing component of its project before disconnecting.|[2,*] :count dates are still on sale. Withdraw them in the ticketing component of their projects before disconnecting.',
                $onSale,
                ['count' => $onSale],
            ));
        }

        $this->forget($connection);
    }

    /** Verbunden und noch im Testmodus — für Hinweise in jeder Ansicht des Ticketings. */
    public function isTestMode(): bool
    {
        return TicketingConnection::query()->whereNull('live_at')->exists();
    }

    /**
     * Im Testmodus: Benutzername und Passwort, mit denen der Shop sich öffnet; live null.
     *
     * @return array{user: string, password: string}|null
     * @throws TicketingConnectionException
     */
    public function shopAccess(TicketingConnection $connection): ?array
    {
        $access = $this->tickets->get($connection, '/house/test-mode')['shopAccess'] ?? null;

        return is_array($access)
            ? ['user' => (string) $access['user'], 'password' => (string) $access['password']]
            : null;
    }

    /** @throws TicketingConnectionException */
    public function saveShopAccess(TicketingConnection $connection, string $user, string $password): void
    {
        $this->tickets->put($connection, '/house/test-mode', ['user' => $user, 'password' => $password]);
    }

    /**
     * Ende des Testmodus: tickets löscht alles Verkaufte und behält die Einrichtung, soweit gewählt.
     * Termine, die dabei aus dem Shop gehen, werden hier wieder Entwürfe. Das Auszahlungskonto
     * prüft der Zahlungsdienstleister neu; ab jetzt kommen auch Käufer ins CRM.
     *
     * @param array{keep_shop_look: bool, keep_pass_types: bool, keep_coupons: bool, keep_dates: bool} $choice
     * @throws TicketingConnectionException
     */
    public function goLive(TicketingConnection $connection, array $choice): void
    {
        $this->assertTestMode($connection);

        $result = $this->tickets->post($connection, '/house/go-live', [
            'keepShopLook' => $choice['keep_shop_look'],
            'keepPassTypes' => $choice['keep_pass_types'],
            'keepCoupons' => $choice['keep_coupons'],
            'keepDates' => $choice['keep_dates'],
        ], timeout: 30);

        DB::transaction(function () use ($connection, $result): void {
            $this->resetReleases(TicketingEventRelease::query()->whereIn(
                'tickets_date_id',
                array_map('strval', (array) ($result['withdrawnDateIds'] ?? [])),
            ));
            $connection->update(['live_at' => now()]);
        });

        SyncTicketingCustomersJob::dispatch();
    }

    /**
     * Neu anfangen: im Testmodus löscht tickets das ganze Haus, hier gehen Verbindung und alle
     * Verweise darauf. Freigegebene Termine werden wieder Entwürfe, ihre Einstellungen bleiben.
     *
     * @throws TicketingConnectionException
     */
    public function erase(): void
    {
        $connection = $this->current();

        if (!$connection) {
            return;
        }

        $this->assertTestMode($connection);

        $this->tickets->delete($connection, '/house');

        $this->resetReleases(TicketingEventRelease::query());
        $this->forget($connection);
    }

    /** @throws TicketingConnectionException */
    private function assertTestMode(TicketingConnection $connection): void
    {
        if (!$connection->isTestMode()) {
            throw new TicketingConnectionException(
                __('This ticket house already sells for real; test mode has ended.')
            );
        }
    }

    /** @param Builder<TicketingEventRelease> $releases */
    private function resetReleases(Builder $releases): void
    {
        $releases->where('state', TicketingEventRelease::STATE_RELEASED)->update([
            'state' => TicketingEventRelease::STATE_DRAFT,
            'tickets_date_id' => null,
            'released_at' => null,
            'released_by_user_id' => null,
            'sync_error' => null,
        ]);
    }

    /** Zugang und Verweise auf das bisherige Haus; ein neu verbundenes Haus kennt sie nicht. */
    private function forget(TicketingConnection $connection): void
    {
        $client = Client::query()->find($connection->oauth_client_id);

        if ($client) {
            $this->clients->delete($client);
        }

        DB::transaction(function () use ($connection): void {
            TicketingRoomLink::query()->delete();
            TicketingProduction::query()->update(['production_id' => null, 'hero_synced_at' => null]);
            TicketingProductionImage::query()->update(['remote_id' => null]);
            $connection->delete();
        });
    }

    /** Dieselben Regeln wie die Hausadresse in tickets (Umlaute ausgeschrieben, nur a-z, 0-9 und Bindestrich). */
    public static function slugify(string $value): string
    {
        $value = mb_strtolower($value);
        $value = str_replace(['ä', 'ö', 'ü', 'ß'], ['ae', 'oe', 'ue', 'ss'], $value);
        $value = preg_replace('/[^a-z0-9]+/', '-', \Normalizer::normalize($value, \Normalizer::FORM_D) ?: $value) ?? '';

        return trim($value, '-');
    }

    private function assertConfigured(): void
    {
        if (!$this->isConfigured()) {
            throw new TicketingConnectionException(__('Artwork-Tickets is not configured for this installation.'));
        }
    }
}
