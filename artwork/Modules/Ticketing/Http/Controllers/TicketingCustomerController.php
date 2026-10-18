<?php

namespace Artwork\Modules\Ticketing\Http\Controllers;

use App\Http\Controllers\Controller;
use Artwork\Modules\Crm\Models\CrmContact;
use Artwork\Modules\Ticketing\Exceptions\TicketingConnectionException;
use Artwork\Modules\Ticketing\Jobs\SyncTicketingCustomersJob;
use Artwork\Modules\Ticketing\Models\TicketingCustomer;
use Artwork\Modules\Ticketing\Services\TicketingConnectionService;
use Artwork\Modules\Ticketing\Services\TicketsClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

/** CRM-Typ "Ticketing-Kunde": Abgleich auf Knopfdruck und die Bestellungen einer Person. */
class TicketingCustomerController extends Controller
{
    public function __construct(
        private readonly TicketingConnectionService $connections,
        private readonly TicketsClient $tickets,
    ) {
    }

    public function sync(): RedirectResponse
    {
        if (!$this->connections->current()) {
            return back()->with('error', __('This installation is not connected to Artwork-Tickets.'));
        }

        SyncTicketingCustomersJob::dispatch();

        return back();
    }

    public function show(CrmContact $crmContact): JsonResponse
    {
        $connection = $this->connections->current();
        $customerId = TicketingCustomer::query()->where('crm_contact_id', $crmContact->id)->value('customer_id');

        if (!$connection || $customerId === null) {
            abort(404);
        }

        try {
            $customer = $this->tickets->get($connection, '/customers/' . rawurlencode($customerId))['customer'];
        } catch (TicketingConnectionException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json(['customer' => $customer]);
    }
}
