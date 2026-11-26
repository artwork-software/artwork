<?php

namespace Artwork\Modules\Ticketing\Http\Controllers;

use App\Http\Controllers\Controller;
use Artwork\Modules\Ticketing\Http\Controllers\Concerns\CallsTickets;
use Artwork\Modules\Ticketing\Http\Requests\SaveTicketingDiscountRequest;
use Artwork\Modules\Ticketing\Models\TicketingConnection;
use Artwork\Modules\Ticketing\Services\TicketingConnectionService;
use Artwork\Modules\Ticketing\Services\TicketingDiscountService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/** Einstellungen → Artwork-Tickets → Rabatte & Pakete: Frühbucher, Staffeln und Pakete, die der Shop von selbst anwendet. */
class TicketingDiscountController extends Controller
{
    use CallsTickets;

    public function __construct(
        private readonly TicketingConnectionService $connections,
        private readonly TicketingDiscountService $discounts,
    ) {
    }

    public function index(Request $request): Response
    {
        $connection = $this->connections->current();
        [$overview, $ticketsError] = $this->readFromTickets(
            $connection,
            $this->discounts->overview(...),
            ['discounts' => [], 'productions' => [], 'groups' => []],
        );

        return Inertia::render('Settings/Tickets/Discounts', [
            'connection' => [
                'connected' => $connection !== null,
            ],
            'discounts' => $overview['discounts'],
            'productions' => $overview['productions'],
            'groups' => $overview['groups'],
            'ticketsError' => $ticketsError,
            // "Paket anlegen" aus dem Ticketing-Tab eines Projekts: der Dialog öffnet sich dafür vorbelegt.
            'packageFor' => $request->string('package')->toString() ?: null,
            // Ein Rabatt aus dem Ticketing-Tab angeklickt: sein Dialog öffnet sich gleich.
            'editDiscount' => $request->string('discount')->toString() ?: null,
        ]);
    }

    public function store(SaveTicketingDiscountRequest $request): RedirectResponse
    {
        return $this->saveToTickets(
            $this->connections->current(),
            fn (TicketingConnection $connection) => $this->discounts->create($connection, $request->validated()),
        );
    }

    public function update(SaveTicketingDiscountRequest $request, string $discount): RedirectResponse
    {
        return $this->saveToTickets(
            $this->connections->current(),
            fn (TicketingConnection $connection) => $this->discounts
                ->update($connection, $discount, $request->validated()),
        );
    }

    public function destroy(string $discount): RedirectResponse
    {
        return $this->sendToTickets(
            $this->connections->current(),
            fn (TicketingConnection $connection) => $this->discounts->delete($connection, $discount),
            __('Deleted in Artwork-Tickets.'),
        );
    }
}
