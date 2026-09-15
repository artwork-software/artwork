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
use Artwork\Modules\Ticketing\Services\TicketingConnectionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Einstellungen → artwork tickets: Übersicht (Verbindung), Räume & Preisklassen, Ermäßigungen.
 * Die Berechtigung "manage ticketing" liegt auf der Routengruppe.
 */
class TicketingConnectionController extends Controller
{
    public function __construct(private readonly TicketingConnectionService $connections)
    {
    }

    public function index(): Response
    {
        return Inertia::render('Settings/Tickets/Index', [
            'connection' => $this->connectionProps(),
            'houseDefaults' => $this->connections->houseDefaults(),
            'countries' => TicketingDraftRules::COUNTRIES,
            'rooms' => $this->permanentRooms(),
        ]);
    }

    /** Nach dem Verbinden: die Räume mit dem Stand aus tickets vorbelegt, damit ein Abgleich nichts überschreibt. */
    public function rooms(): Response
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
            'houseDefaults' => $this->connections->houseDefaults(),
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

        return back()->with('success', __('Connected to artwork tickets.'));
    }

    public function syncRooms(SyncRoomsRequest $request): RedirectResponse
    {
        $connection = $this->requireConnection();

        try {
            $this->connections->syncRooms($connection, $request->validated('rooms'));
        } catch (TicketingConnectionException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', __('Rooms synced with artwork tickets.'));
    }

    public function syncReductions(SyncReductionsRequest $request): RedirectResponse
    {
        $connection = $this->requireConnection();

        try {
            $this->connections->syncReductions($connection, $request->validated('reductions'));
        } catch (TicketingConnectionException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', __('Reductions synced with artwork tickets.'));
    }

    public function destroy(): RedirectResponse
    {
        $this->connections->disconnect();

        return back()->with('success', __('Disconnected from artwork tickets.'));
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
            'dashboardUrl' => $connection?->dashboard_url,
            'connectedAt' => $connection?->created_at,
            'connectedBy' => $connection?->connectedBy?->full_name,
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
            ?? abort(409, __('This installation is not connected to artwork tickets yet.'));
    }
}
