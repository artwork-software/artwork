<?php

namespace Artwork\Modules\Ticketing\Http\Controllers;

use App\Http\Controllers\Controller;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Ticketing\Exceptions\TicketingConnectionException;
use Artwork\Modules\Ticketing\Http\Requests\TicketingEventsRequest;
use Artwork\Modules\Ticketing\Services\TicketingConnectionService;
use Artwork\Modules\Ticketing\Services\TicketingReleaseService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Was Kalender und Ticketdetails außerhalb der Projekt-Komponente von tickets brauchen. */
class TicketingCalendarController extends Controller
{
    public function __construct(
        private readonly TicketingConnectionService $connections,
        private readonly TicketingReleaseService $releases,
    ) {
    }

    /** Verkauft/Plätze je freigegebenem Termin im geladenen Kalenderzeitraum. */
    public function summary(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
        ]);

        try {
            return response()->json($this->releases->calendarSummary(
                $request->user(),
                Carbon::parse($validated['start_date'])->startOfDay(),
                Carbon::parse($validated['end_date'])->endOfDay(),
            ));
        } catch (TicketingConnectionException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }
    }

    /**
     * Verkaufsstand und Gästeliste eines Termins aus tickets; ohne Freigabe nur "nicht freigegeben".
     * Mit only_state bleibt es bei der Frage, ob er freigegeben ist — die kostet keinen Aufruf nach tickets.
     * Nur über den Termin adressiert, damit jede Ansicht, die ihn kennt, die Details öffnen kann.
     */
    public function sales(Event $event, Request $request): JsonResponse
    {
        $this->authorizeEvent($event, $request);

        if ($request->boolean('only_state')) {
            return response()->json(['released' => $this->releases->isReleased($event)]);
        }

        try {
            return response()->json($this->releases->sales($event));
        } catch (TicketingConnectionException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }
    }

    /**
     * Die Rückfrage vor dem Verschieben: welche der Termine im Verkauf sind und ob die Person sie
     * verschieben darf; mit with_series samt den Serien der Termine. Die Sperre selbst sitzt im TicketingLock.
     */
    public function moveCheck(TicketingEventsRequest $request): JsonResponse
    {
        $seriesIds = $request->boolean('with_series')
            ? Event::query()->whereKey($request->eventIds())->where('is_series', true)->whereNotNull('series_id')->pluck('series_id')
            : [];
        $events = Event::query()
            ->where(static fn ($query) => $query->whereKey($request->eventIds())->orWhereIn('series_id', $seriesIds))
            ->with(['ticketingRelease', 'project'])
            ->get()
            ->filter(static fn (Event $event): bool => $event->project !== null
                && $request->user()->can('view', $event->project));

        return response()->json([
            'may_move' => $request->user()->can(PermissionEnum::TICKETING_MOVE_ON_SALE->value),
            'dates' => $this->releases->onSale($events),
        ]);
    }

    /** Weiter ins Ticket-Dashboard, mit ?event= gleich zu diesem Termin, mit ?to=settings zu den Haus-Einstellungen. */
    public function open(Request $request): RedirectResponse
    {
        $connection = $this->connections->current();
        abort_unless($connection !== null, 404);

        return redirect()->away($this->connections->loginUrl($connection, $request->user(), $this->destination($request)));
    }

    /** @return array{type: 'dashboard'}|array{type: 'date', dateId: string}|array{type: 'houseSettings'} */
    private function destination(Request $request): array
    {
        if ($request->query('to') === 'settings') {
            return ['type' => 'houseSettings'];
        }

        if ($request->filled('event')) {
            $event = Event::query()->findOrFail($request->integer('event'));
            $this->authorizeEvent($event, $request);

            if ($this->releases->isReleased($event)) {
                return ['type' => 'date', 'dateId' => $event->ticketingRelease->tickets_date_id];
            }
        }

        return ['type' => 'dashboard'];
    }

    /** Ticketdaten eines Termins sieht, wer sein Projekt sieht. */
    private function authorizeEvent(Event $event, Request $request): void
    {
        abort_unless($event->project && $request->user()->can('view', $event->project), 403);
    }
}
