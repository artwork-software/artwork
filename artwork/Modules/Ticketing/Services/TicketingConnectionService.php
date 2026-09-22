<?php

namespace Artwork\Modules\Ticketing\Services;

use Artwork\Modules\GeneralSettings\Models\GeneralSettings;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\Ticketing\Exceptions\TicketingConnectionException;
use Artwork\Modules\Ticketing\Models\TicketingConnection;
use Artwork\Modules\Ticketing\Models\TicketingRoomLink;
use Artwork\Modules\User\Models\User;
use Laravel\Passport\Client;
use Laravel\Passport\ClientRepository;

/**
 * Handshake mit artwork tickets: diese Instanz legt einen OAuth-Client (client_credentials) an,
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
    public const CLIENT_NAME = 'artwork tickets';

    public function __construct(
        private readonly GeneralSettings $generalSettings,
        private readonly ClientRepository $clients,
        private readonly TicketsClient $tickets,
    ) {
    }

    public function isConfigured(): bool
    {
        return (bool) config('services.tickets.url') && (bool) config('services.tickets.provisioning_secret');
    }

    public function current(): ?TicketingConnection
    {
        return TicketingConnection::query()->with('connectedBy')->first();
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
                'account_holder' => $this->generalSettings->letterhead_name ?: $name,
                'iban' => '',
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
     *     billing: array<string, string|null>|null,
     *     rooms: list<array<string, mixed>>,
     *     reductions: list<array<string, mixed>>
     * } $draft
     */
    public function connect(User $user, array $draft): TicketingConnection
    {
        $this->assertConfigured();

        if ($this->current()) {
            throw new TicketingConnectionException(__('This installation is already connected to artwork tickets.'));
        }

        $client = $this->clients->createClientCredentialsGrantClient(self::CLIENT_NAME);

        try {
            $house = $this->tickets->provisioningPost('/houses', [
                'name' => $draft['house']['name'],
                'slug' => $draft['house']['slug'],
                'ownerEmail' => $user->email,
                'ownerName' => $user->full_name,
                'billing' => $draft['billing'] === null ? null : TicketingBillingService::payload($draft['billing']),
                'coreUrl' => config('app.url'),
                // Als String: je nach Installation ist der Client-Schlüssel numerisch oder eine UUID.
                'coreClientId' => (string) $client->getKey(),
                'coreClientSecret' => $client->plainSecret,
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
        ]);

        // Ab hier besteht die Verbindung; scheitert der Abgleich, bleibt sie und die Meldung nennt den Rest.
        try {
            $this->syncRooms($connection, $draft['rooms']);
            $this->syncReductions($connection, $draft['reductions']);
        } catch (TicketingConnectionException $exception) {
            throw new TicketingConnectionException(__(
                'Connected to artwork tickets, but the sync failed: :message',
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
            ?? throw new TicketingConnectionException(__('artwork tickets sent an unexpected answer.')))->keyBy('id');
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
                    'defaultPriceCents' => $zone['default_price_cents'],
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

    public function disconnect(): void
    {
        $connection = $this->current();

        if (!$connection) {
            return;
        }

        $client = Client::query()->find($connection->oauth_client_id);

        if ($client) {
            $this->clients->delete($client);
        }

        $connection->delete();
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
            throw new TicketingConnectionException(__('artwork tickets is not configured for this installation.'));
        }
    }
}
