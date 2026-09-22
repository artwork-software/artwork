<?php

namespace Artwork\Modules\Ticketing\Http\Controllers;

use App\Http\Controllers\Controller;
use Artwork\Modules\Ticketing\Exceptions\TicketingConnectionException;
use Artwork\Modules\Ticketing\Http\Requests\SaveTicketingBillingRequest;
use Artwork\Modules\Ticketing\Http\Requests\TicketingDraftRules;
use Artwork\Modules\Ticketing\Services\TicketingBillingService;
use Artwork\Modules\Ticketing\Services\TicketingConnectionService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/** Einstellungen → artwork tickets → Angaben & Bankverbindung: was der Assistent offen lassen durfte. */
class TicketingBillingController extends Controller
{
    public function __construct(
        private readonly TicketingConnectionService $connections,
        private readonly TicketingBillingService $billing,
    ) {
    }

    public function index(): Response
    {
        $connection = $this->connections->current();
        $billing = null;
        $ticketsError = null;

        if ($connection) {
            try {
                $billing = $this->billing->status($connection);
            } catch (TicketingConnectionException $exception) {
                $ticketsError = $exception->getMessage();
            }
        }

        return Inertia::render('Settings/Tickets/Billing', [
            'connection' => [
                'connected' => $connection !== null,
                'dashboardUrl' => $connection?->dashboard_url,
            ],
            'billing' => $billing,
            'countries' => TicketingDraftRules::COUNTRIES,
            'legalForms' => TicketingDraftRules::LEGAL_FORMS,
            'ticketsError' => $ticketsError,
        ]);
    }

    public function update(SaveTicketingBillingRequest $request): RedirectResponse
    {
        $connection = $this->connections->current();

        if (!$connection) {
            return back()->with('error', __('This installation is not connected to artwork tickets.'));
        }

        try {
            $this->billing->save($connection, $request->validated());
        } catch (TicketingConnectionException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', __('Details saved in artwork tickets.'));
    }
}
