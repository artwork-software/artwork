<?php

namespace App\Http\Controllers;

use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Event\Models\SeriesEvents;
use Artwork\Modules\Event\Services\SeriesEventsService;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Wiederholungstermine (KONZEPT_Wiederholungstermine.md): Vorbelegung + Serien-Tab, Vorschau beim
 * Anlegen, Warnung vor Turnus-Wechsel, Termin lösen / Serie beenden, Serie wiederherstellen.
 * Bearbeiten/Löschen mit Reichweite laufen weiter über EventController (events.update / events.series.delete).
 */
class EventSeriesController extends Controller
{
    public function __construct(private readonly SeriesEventsService $seriesEventsService)
    {
    }

    /**
     * Seriendefinition + alle Termine der Serie (für Modal-Vorbelegung und Serien-Tab).
     * Wird beim Öffnen eines Serientermins nachgeladen, damit das Kalender-Paket schlank bleibt.
     */
    public function show(Event $event): JsonResponse
    {
        return response()->json($this->seriesEventsService->getSeriesPayload($event));
    }

    /**
     * Vorschau beim Anlegen: welche Termine erzeugt der gewählte Turnus, mit Raumkollisionen je Termin.
     * @throws AuthorizationException
     */
    public function preview(Request $request): JsonResponse
    {
        $this->authorize('create', Event::class);

        $validated = $request->validate([
            'start' => ['required', 'date'],
            'end' => ['required', 'date', 'after:start'],
            'roomId' => ['nullable', 'integer'],
            'seriesFrequency' => ['required', 'integer', 'in:1,2,3,4'],
            'seriesEndDate' => ['nullable', 'date'],
            'seriesWeekdays' => ['nullable', 'array'],
            'seriesWeekdays.*' => ['integer', 'between:1,7'],
            'seriesOccurrenceCount' => ['nullable', 'integer', 'between:1,500'],
        ]);

        if (empty($validated['seriesEndDate']) && empty($validated['seriesOccurrenceCount'])) {
            throw ValidationException::withMessages([
                'seriesEndDate' => __('Please choose an end date or a number of events for the series.'),
            ]);
        }

        $start = Carbon::parse($validated['start'])->setTimezone(config('app.timezone'));
        $end = Carbon::parse($validated['end'])->setTimezone(config('app.timezone'));

        return response()->json($this->seriesEventsService->preview(
            $start,
            $end,
            $this->definitionInput($request),
            $validated['roomId'] ?? null
        ));
    }

    /**
     * Dry-Run einer Turnus-/Ende-Änderung: wie viele Termine (und Schichten) gehen in den Papierkorb,
     * wie viele entstehen neu. Grundlage für die Warnung im Modal.
     * @throws AuthorizationException
     */
    public function impact(Event $event, Request $request): JsonResponse
    {
        $this->authorize('update', $event);

        /** @var SeriesEvents|null $series */
        $series = $event->series_id ? SeriesEvents::query()->find($event->series_id) : null;
        if (!$series) {
            return response()->json([
                'changed' => false, 'rebuild' => false, 'trash' => 0, 'shifts' => 0, 'create' => 0,
            ]);
        }

        $request->validate([
            'seriesFrequency' => ['required', 'integer', 'in:1,2,3,4'],
            'seriesEndDate' => ['nullable', 'date'],
            'seriesWeekdays' => ['nullable', 'array'],
            'seriesWeekdays.*' => ['integer', 'between:1,7'],
            'seriesOccurrenceCount' => ['nullable', 'integer', 'between:1,500'],
        ]);

        return response()->json($this->seriesEventsService->impact($event, $series, $this->definitionInput($request)));
    }

    /**
     * mode=single: nur diesen Termin aus der Serie lösen.
     * mode=end: Serie beenden – alle anderen Termine in den Papierkorb, dieser wird Einzeltermin.
     * @throws AuthorizationException
     */
    public function detach(Event $event, Request $request): JsonResponse
    {
        $this->authorize('update', $event);

        $mode = $request->input('mode') === 'end' ? 'end' : 'single';

        if (!$event->is_series || !$event->series_id) {
            return response()->json(['trashed' => 0]);
        }

        if ($mode === 'end') {
            $others = Event::query()->where('series_id', $event->series_id)->where('id', '!=', $event->id)->get();
            foreach ($others as $other) {
                $this->authorize('delete', $other);
            }
        }

        return response()->json(['trashed' => $this->seriesEventsService->detach($event, $mode)]);
    }

    /**
     * Alle Termine einer Serie aus dem Papierkorb wiederherstellen.
     */
    public function restore(SeriesEvents $series): JsonResponse
    {
        return response()->json(['restored' => $this->seriesEventsService->restoreSeries($series)]);
    }

    /**
     * @return array<string, mixed>
     */
    private function definitionInput(Request $request): array
    {
        return [
            'frequency' => $request->input('seriesFrequency'),
            'end_date' => $request->input('seriesEndDate'),
            'weekdays' => $request->input('seriesWeekdays'),
            'occurrence_count' => $request->input('seriesOccurrenceCount'),
        ];
    }
}
