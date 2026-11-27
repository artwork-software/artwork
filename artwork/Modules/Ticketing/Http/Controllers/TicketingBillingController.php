<?php

namespace Artwork\Modules\Ticketing\Http\Controllers;

use App\Http\Controllers\Controller;
use Artwork\Modules\Ticketing\Exceptions\TicketingConnectionException;
use Artwork\Modules\Ticketing\Http\Controllers\Concerns\CallsTickets;
use Artwork\Modules\Ticketing\Http\Requests\SaveTicketingBillingRequest;
use Artwork\Modules\Ticketing\Http\Requests\TicketingDraftRules;
use Artwork\Modules\Ticketing\Models\TicketingConnection;
use Artwork\Modules\Ticketing\Services\TicketingBillingService;
use Artwork\Modules\Ticketing\Services\TicketingConnectionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/** Einstellungen → Artwork-Tickets → Angaben & Auszahlung: was der Assistent offen lassen durfte, und Stripes Formular. */
class TicketingBillingController extends Controller
{
    use CallsTickets;

    public function __construct(
        private readonly TicketingConnectionService $connections,
        private readonly TicketingBillingService $billing,
    ) {
    }

    public function index(): Response
    {
        $connection = $this->connections->current();
        [$billing, $ticketsError] = $this->readFromTickets($connection, $this->billing->status(...), null);

        return Inertia::render('Settings/Tickets/Billing', [
            'connection' => [
                'connected' => $connection !== null,
            ],
            'billing' => $billing,
            'countries' => TicketingDraftRules::COUNTRIES,
            'legalForms' => TicketingDraftRules::LEGAL_FORMS,
            'ticketsError' => $ticketsError,
        ]);
    }

    public function update(SaveTicketingBillingRequest $request): RedirectResponse
    {
        return $this->sendToTickets(
            $this->connections->current(),
            fn (TicketingConnection $connection) => $this->billing->save($connection, $request->validated()),
            __('Details saved in Artwork-Tickets.'),
        );
    }

    public function removeLegalDocument(string $document): RedirectResponse
    {
        return $this->sendToTickets(
            $this->connections->current(),
            fn (TicketingConnection $connection) => $this->billing->removeLegalDocument($connection, $document),
            __('PDF removed in Artwork-Tickets.'),
        );
    }

    public function acceptPlatformTerms(Request $request): RedirectResponse
    {
        return $this->sendToTickets(
            $this->connections->current(),
            fn (TicketingConnection $connection) => $this->billing->acceptPlatformTerms($connection, $request->user()),
            __('Terms accepted for the house.'),
        );
    }

    public function setFeeBearer(Request $request): RedirectResponse
    {
        $bearer = $request->validate([
            'bearer' => ['required', Rule::in(TicketingBillingService::FEE_BEARERS)],
        ])['bearer'];

        return $this->sendToTickets(
            $this->connections->current(),
            fn (TicketingConnection $connection) => $this->billing->setFeeBearer($connection, $bearer),
            __('Fee setting saved in Artwork-Tickets.'),
        );
    }

    /** Für Stripes eingebettetes Formular, das sich das Geheimnis selbst holt — auch erneut, wenn es abläuft. */
    public function stripeSession(Request $request): JsonResponse
    {
        $connection = $this->connections->current();

        if (!$connection) {
            return response()->json(['message' => __('This installation is not connected to Artwork-Tickets.')], 409);
        }

        try {
            return response()->json([
                'clientSecret' => $this->billing->stripeSession($connection, $request->user()->email),
            ]);
        } catch (TicketingConnectionException $exception) {
            return response()->json(['message' => $exception->getMessage()], 502);
        }
    }
}
