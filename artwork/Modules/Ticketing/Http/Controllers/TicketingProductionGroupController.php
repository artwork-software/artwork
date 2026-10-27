<?php

namespace Artwork\Modules\Ticketing\Http\Controllers;

use App\Http\Controllers\Controller;
use Artwork\Modules\Ticketing\Http\Controllers\Concerns\CallsTickets;
use Artwork\Modules\Ticketing\Http\Requests\SaveTicketingProductionGroupRequest;
use Artwork\Modules\Ticketing\Models\TicketingConnection;
use Artwork\Modules\Ticketing\Services\TicketingConnectionService;
use Artwork\Modules\Ticketing\Services\TicketingProductionGroupService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Einstellungen → Artwork-Tickets → Produktionsgruppen: Gruppen von Produktionen ("Workshops"), für
 * die Karten, Abos und Rabattcodes in Artwork-Tickets als Ganzes gelten.
 */
class TicketingProductionGroupController extends Controller
{
    use CallsTickets;

    public function __construct(
        private readonly TicketingConnectionService $connections,
        private readonly TicketingProductionGroupService $groups,
    ) {
    }

    public function index(): Response
    {
        $connection = $this->connections->current();
        [$overview, $ticketsError] = $this->readFromTickets(
            $connection,
            $this->groups->overview(...),
            ['groups' => [], 'productions' => []],
        );

        return Inertia::render('Settings/Tickets/Groups', [
            'connection' => [
                'connected' => $connection !== null,
            ],
            'groups' => $overview['groups'],
            'productions' => $overview['productions'],
            'ticketsError' => $ticketsError,
        ]);
    }

    public function store(SaveTicketingProductionGroupRequest $request): RedirectResponse
    {
        return $this->saveToTickets(
            $this->connections->current(),
            fn (TicketingConnection $connection) => $this->groups->create($connection, $request->validated()),
        );
    }

    public function update(SaveTicketingProductionGroupRequest $request, string $group): RedirectResponse
    {
        return $this->saveToTickets(
            $this->connections->current(),
            fn (TicketingConnection $connection) => $this->groups->update($connection, $group, $request->validated()),
        );
    }

    public function destroy(string $group): RedirectResponse
    {
        return $this->sendToTickets(
            $this->connections->current(),
            fn (TicketingConnection $connection) => $this->groups->delete($connection, $group),
            __('Deleted in Artwork-Tickets.'),
        );
    }
}
