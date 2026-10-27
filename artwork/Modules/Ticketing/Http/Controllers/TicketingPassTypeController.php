<?php

namespace Artwork\Modules\Ticketing\Http\Controllers;

use App\Http\Controllers\Controller;
use Artwork\Modules\Ticketing\Http\Controllers\Concerns\CallsTickets;
use Artwork\Modules\Ticketing\Http\Requests\SaveTicketingPassTypeRequest;
use Artwork\Modules\Ticketing\Models\TicketingConnection;
use Artwork\Modules\Ticketing\Services\TicketingConnectionService;
use Artwork\Modules\Ticketing\Services\TicketingPassTypeService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/** Einstellungen → Artwork-Tickets → Karten & Abos: Mehrfachkarten und Abos des Hauses anlegen und pflegen. */
class TicketingPassTypeController extends Controller
{
    use CallsTickets;

    public function __construct(
        private readonly TicketingConnectionService $connections,
        private readonly TicketingPassTypeService $passTypes,
    ) {
    }

    public function index(): Response
    {
        $connection = $this->connections->current();
        [$overview, $ticketsError] = $this->readFromTickets(
            $connection,
            $this->passTypes->overview(...),
            ['passTypes' => [], 'productions' => [], 'groups' => []],
        );

        return Inertia::render('Settings/Tickets/Passes', [
            'connection' => [
                'connected' => $connection !== null,
            ],
            'passTypes' => $overview['passTypes'],
            'productions' => $overview['productions'],
            'groups' => $overview['groups'],
            'ticketsError' => $ticketsError,
        ]);
    }

    public function store(SaveTicketingPassTypeRequest $request): RedirectResponse
    {
        return $this->saveToTickets(
            $this->connections->current(),
            fn (TicketingConnection $connection) => $this->passTypes->create($connection, $request->validated()),
        );
    }

    public function update(SaveTicketingPassTypeRequest $request, string $passType): RedirectResponse
    {
        return $this->saveToTickets(
            $this->connections->current(),
            fn (TicketingConnection $connection) => $this->passTypes
                ->update($connection, $passType, $request->validated()),
        );
    }

    public function publish(string $passType): RedirectResponse
    {
        return $this->sendToTickets(
            $this->connections->current(),
            fn (TicketingConnection $connection) => $this->passTypes->publish($connection, $passType),
            __('Now on sale in Artwork-Tickets.'),
        );
    }

    public function archive(string $passType): RedirectResponse
    {
        return $this->sendToTickets(
            $this->connections->current(),
            fn (TicketingConnection $connection) => $this->passTypes->archive($connection, $passType),
            __('Archived. Issued passes and running subscriptions stay valid.'),
        );
    }

    public function destroy(string $passType): RedirectResponse
    {
        return $this->sendToTickets(
            $this->connections->current(),
            fn (TicketingConnection $connection) => $this->passTypes->delete($connection, $passType),
            __('Deleted in Artwork-Tickets.'),
        );
    }
}
