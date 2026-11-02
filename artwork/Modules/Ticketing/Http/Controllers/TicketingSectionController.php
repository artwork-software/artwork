<?php

namespace Artwork\Modules\Ticketing\Http\Controllers;

use App\Http\Controllers\Controller;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Ticketing\Exceptions\TicketingConnectionException;
use Artwork\Modules\Ticketing\Http\Controllers\Concerns\CallsTickets;
use Artwork\Modules\Ticketing\Models\TicketingConnection;
use Artwork\Modules\Ticketing\Services\TicketingConnectionService;
use Artwork\Modules\Ticketing\Services\TicketingSalesService;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Hauptmenü → Artwork-Tickets: Verkäufe sowie verkaufte Karten und Abos, gelesen aus tickets. Wer Karten und
 * Abos verwalten darf, korrigiert sie im Dialog einer Zeile.
 */
class TicketingSectionController extends Controller
{
    use CallsTickets;

    public function __construct(
        private readonly TicketingConnectionService $connections,
        private readonly TicketingSalesService $sales,
    ) {
    }

    public function sales(Request $request): Response
    {
        $query = $request->validate([
            'days' => ['nullable', 'in:7,30,90'],
            'from' => ['nullable', 'date_format:Y-m-d', 'required_with:to', 'before_or_equal:to'],
            'to' => ['nullable', 'date_format:Y-m-d', 'required_with:from'],
            'events' => ['nullable', 'array', 'max:100'],
            'events.*' => ['uuid'],
        ]);

        return $this->render(
            'Ticketing/Sales',
            ['filters' => $query],
            'sales',
            fn ($connection): array => $this->sales->sales($connection, $query),
        );
    }

    public function passes(Request $request): Response
    {
        $filters = $this->holdingFilters($request);

        return $this->render(
            'Ticketing/Passes',
            [
                'filters' => $filters,
                'mayManage' => $request->user()->can(PermissionEnum::TICKETING_MANAGE_PASSES->value),
            ],
            'holdings',
            fn ($connection): array => $this->sales->holdings($connection, $filters),
        );
    }

    /** Die Liste als JSON: der Dialog liest seine Zeile nach einer Änderung neu, auch wenn der Filter sie nicht mehr zeigt. */
    public function holdings(Request $request): JsonResponse
    {
        $filters = $this->holdingFilters($request);

        return $this->respond(fn (TicketingConnection $connection): array => [
            'holdings' => $this->sales->holdings($connection, $filters),
        ]);
    }

    /** Eine Karte mit ihrem Verlauf, für den Dialog einer Zeile. */
    public function pass(string $pass): JsonResponse
    {
        return $this->respond(fn (TicketingConnection $connection): array => [
            'pass' => $this->sales->pass($connection, $pass),
        ]);
    }

    /** Nutzungen gutschreiben oder abziehen, das Ende der Gültigkeit ändern, sperren oder entsperren. */
    public function correctPass(string $pass, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'kind' => ['required', 'in:adjust,validUntil,revoke,reactivate'],
            'delta' => ['required_if:kind,adjust', 'integer', 'between:-500,500', 'not_in:0'],
            'note' => ['nullable', 'string', 'max:500'],
            'day' => ['nullable', 'date_format:Y-m-d'],
        ]);
        $command = match ($validated['kind']) {
            'adjust' => ['kind' => 'adjust', 'delta' => (int) $validated['delta'], 'note' => $validated['note'] ?? ''],
            'validUntil' => ['kind' => 'validUntil', 'day' => $validated['day'] ?? null],
            default => ['kind' => $validated['kind']],
        };

        return $this->respond(fn (TicketingConnection $connection): array => [
            'pass' => $this->sales->correctPass($connection, $pass, $command, $request->user()),
        ]);
    }

    /** Kündigen, sofort beenden, pausieren, die Pause beenden oder eine Kündigung zurücknehmen. */
    public function changeSubscription(string $subscription, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'kind' => ['required', 'in:cancel,endNow,pause,resume,withdrawCancellation'],
            'resumes_on' => ['required_if:kind,pause', 'nullable', 'date_format:Y-m-d', 'after:today'],
        ]);
        $command = $validated['kind'] === 'pause'
            ? ['kind' => 'pause', 'resumesOn' => $validated['resumes_on']]
            : ['kind' => $validated['kind']];

        return $this->respond(function (TicketingConnection $connection) use ($subscription, $command, $request): array {
            $this->sales->changeSubscription($connection, $subscription, $command, $request->user());

            return ['done' => true];
        });
    }

    /** @return array{kind?: string, state?: string, q?: string} */
    private function holdingFilters(Request $request): array
    {
        return $request->validate([
            'kind' => ['nullable', 'in:pass,subscription'],
            'q' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'in:running,pastDue,ended'],
        ]);
    }

    /**
     * Für den Dialog: die Antwort von tickets als JSON, eine Ablehnung als Meldung.
     *
     * @param Closure(TicketingConnection): array<string, mixed> $call
     */
    private function respond(Closure $call): JsonResponse
    {
        $connection = $this->connections->current();

        if (!$connection) {
            return response()->json(['message' => __('This installation is not connected to Artwork-Tickets.')], 422);
        }

        try {
            return response()->json($call($connection));
        } catch (TicketingConnectionException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }
    }

    /**
     * Die Seite mit dem, was tickets liefert; ohne Verbindung oder bei einem Fehler bleibt sie leer und sagt es.
     *
     * @param array<string, mixed> $props
     * @param Closure(TicketingConnection): array<mixed> $load
     */
    private function render(string $component, array $props, string $key, Closure $load): Response
    {
        $connection = $this->connections->current();
        [$props[$key], $ticketsError] = $this->readFromTickets($connection, $load, null);

        return Inertia::render($component, $props + [
            'connected' => $connection !== null,
            'ticketsError' => $ticketsError,
        ]);
    }
}
