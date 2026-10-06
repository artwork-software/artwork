<?php

namespace Artwork\Modules\Ticketing\Http\Controllers;

use App\Http\Controllers\Controller;
use Artwork\Modules\Ticketing\Exceptions\TicketingConnectionException;
use Artwork\Modules\Ticketing\Http\Requests\SaveTicketingBillingRequest;
use Artwork\Modules\Ticketing\Http\Requests\TicketingDraftRules;
use Artwork\Modules\Ticketing\Services\TicketingBillingService;
use Artwork\Modules\Ticketing\Services\TicketingConnectionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/** Einstellungen → artwork tickets → Angaben & Auszahlung: was der Assistent offen lassen durfte, und Stripes Formular. */
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

    public function removeLegalDocument(string $document): RedirectResponse
    {
        $connection = $this->connections->current();

        if (!$connection) {
            return back()->with('error', __('This installation is not connected to artwork tickets.'));
        }

        try {
            $this->billing->removeLegalDocument($connection, $document);
        } catch (TicketingConnectionException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', __('PDF removed in artwork tickets.'));
    }

    public function acceptPlatformTerms(Request $request): RedirectResponse
    {
        $connection = $this->connections->current();

        if (!$connection) {
            return back()->with('error', __('This installation is not connected to artwork tickets.'));
        }

        try {
            $this->billing->acceptPlatformTerms($connection, $request->user());
        } catch (TicketingConnectionException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', __('Terms accepted for the house.'));
    }

    /** Für Stripes eingebettetes Formular, das sich das Geheimnis selbst holt — auch erneut, wenn es abläuft. */
    public function stripeSession(Request $request): JsonResponse
    {
        $connection = $this->connections->current();

        if (!$connection) {
            return response()->json(['message' => __('This installation is not connected to artwork tickets.')], 409);
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
