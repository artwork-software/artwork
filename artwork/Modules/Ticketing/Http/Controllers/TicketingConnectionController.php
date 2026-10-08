<?php

namespace Artwork\Modules\Ticketing\Http\Controllers;

use App\Http\Controllers\Controller;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\Ticketing\Exceptions\TicketingConnectionException;
use Artwork\Modules\Ticketing\Http\Requests\ConnectTicketingRequest;
use Artwork\Modules\Ticketing\Http\Requests\SyncReductionsRequest;
use Artwork\Modules\Ticketing\Http\Requests\SyncRoomsRequest;
use Artwork\Modules\Ticketing\Http\Requests\TicketingDraftRules;
use Artwork\Modules\Ticketing\Models\TicketingConnection;
use Artwork\Modules\Ticketing\Models\TicketingRoomLink;
use Artwork\Modules\Ticketing\Services\TicketingBillingService;
use Artwork\Modules\Ticketing\Services\TicketingConnectionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Einstellungen → Artwork-Tickets: Übersicht (Verbindung), Räume & Preisklassen, Ermäßigungen.
 * Die Berechtigung "manage ticketing" liegt auf der Routengruppe.
 */
class TicketingConnectionController extends Controller
{
    public function __construct(
        private readonly TicketingConnectionService $connections,
        private readonly TicketingBillingService $billing,
    ) {
    }

    public function index(Request $request): Response
    {
        $connection = $this->connections->current();
        $billing = null;

        // Ohne Antwort von tickets keine Warnung und kein Stripe-Formular, die Seite selbst bleibt.
        if ($connection) {
            try {
                $billing = $this->billing->status($connection);
            } catch (TicketingConnectionException) {
            }
        }

        return Inertia::render('Settings/Tickets/Index', [
            'connection' => [
                ...$this->connectionProps(),
                'billingComplete' => $billing === null ? null : TicketingBillingService::complete($billing),
                // Für den letzten Schritt des Assistenten: das Auszahlungskonto, gleich nach dem Verbinden.
                'payout' => $billing === null ? null : [
                    'state' => $billing['payout_account'],
                    'stripe_key' => $billing['stripe_key'],
                ],
            ],
            'houseDefaults' => $this->connections->houseDefaults($request->user()),
            'countries' => TicketingDraftRules::COUNTRIES,
            'legalForms' => TicketingDraftRules::LEGAL_FORMS,
            'rooms' => $this->permanentRooms(),
            'linkedRooms' => TicketingRoomLink::query()->count(),
        ]);
    }

    /** Nach dem Verbinden: die Räume mit dem Stand aus tickets vorbelegt, damit ein Abgleich nichts überschreibt. */
    public function rooms(Request $request): Response
    {
        $connection = $this->connections->current();
        $venues = [];
        $ticketsError = null;

        if ($connection) {
            try {
                $venues = $this->connections->venues($connection);
            } catch (TicketingConnectionException $exception) {
                $ticketsError = $exception->getMessage();
            }
        }

        $links = TicketingRoomLink::query()->pluck('venue_id', 'room_id');

        return Inertia::render('Settings/Tickets/Rooms', [
            'connection' => $this->connectionProps(),
            'houseDefaults' => $this->connections->houseDefaults($request->user()),
            'countries' => TicketingDraftRules::COUNTRIES,
            'rooms' => $this->permanentRooms()->map(static fn (array $room): array => [
                ...$room,
                'venue' => $venues[$links[$room['id']] ?? ''] ?? null,
            ]),
            'ticketsError' => $ticketsError,
        ]);
    }

    public function reductions(): Response
    {
        $connection = $this->connections->current();
        $reductions = [];
        $ticketsError = null;

        if ($connection) {
            try {
                $reductions = $this->connections->reductions($connection);
            } catch (TicketingConnectionException $exception) {
                $ticketsError = $exception->getMessage();
            }
        }

        return Inertia::render('Settings/Tickets/Reductions', [
            'connection' => $this->connectionProps(),
            'reductions' => $reductions,
            'ticketsError' => $ticketsError,
        ]);
    }

    public function checkAvailability(Request $request): JsonResponse
    {
        try {
            return response()->json($this->connections->checkAvailability(
                (string) $request->query('slug', ''),
                (string) $request->query('name', ''),
                $request->user()->email,
            ));
        } catch (TicketingConnectionException $exception) {
            return response()->json(['message' => $exception->getMessage()], 502);
        }
    }

    public function store(ConnectTicketingRequest $request): RedirectResponse
    {
        try {
            $this->connections->connect($request->user(), $request->validated());
        } catch (TicketingConnectionException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        // Zurück in den Assistenten: sein letzter Schritt ist Stripes Formular, das erst das Haus braucht.
        return back()->with('success', __('Connected to Artwork-Tickets.'));
    }

    public function syncRooms(SyncRoomsRequest $request): RedirectResponse
    {
        $connection = $this->requireConnection();

        try {
            $this->connections->syncRooms($connection, $request->validated('rooms'));
        } catch (TicketingConnectionException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', __('Rooms synced with Artwork-Tickets.'));
    }

    public function syncReductions(SyncReductionsRequest $request): RedirectResponse
    {
        $connection = $this->requireConnection();

        try {
            $this->connections->syncReductions($connection, $request->validated('reductions'));
        } catch (TicketingConnectionException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', __('Reductions synced with Artwork-Tickets.'));
    }

    public function destroy(): RedirectResponse
    {
        try {
            $this->connections->disconnect();
        } catch (TicketingConnectionException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', __('Disconnected from Artwork-Tickets.'));
    }

    /** @return array<string, mixed> */
    private function connectionProps(): array
    {
        $connection = $this->connections->current();

        return [
            'configured' => $this->connections->isConfigured(),
            'url' => config('services.tickets.url'),
            'connected' => $connection !== null,
            'organizationSlug' => $connection?->organization_slug,
            'connectedAt' => $connection?->created_at,
            'connectedBy' => $connection?->connectedBy?->full_name,
            'datesOnSale' => $connection === null ? 0 : $this->connections->datesOnSale(),
        ];
    }

    /**
     * Nur dauerhafte Räume: ein temporärer Raum ist keine Spielstätte, die Tickets verkauft.
     *
     * @return Collection<int, array{id: int, name: string, capacity: int|null}>
     */
    private function permanentRooms(): Collection
    {
        return Room::query()
            ->where('temporary', false)
            ->orderBy('order')
            ->get(['id', 'name', 'capacity'])
            ->map(static fn (Room $room): array => [
                'id' => $room->id,
                'name' => $room->name,
                'capacity' => $room->capacity,
            ]);
    }

    private function requireConnection(): TicketingConnection
    {
        return $this->connections->current()
            ?? abort(409, __('This installation is not connected to Artwork-Tickets yet.'));
    }
}
