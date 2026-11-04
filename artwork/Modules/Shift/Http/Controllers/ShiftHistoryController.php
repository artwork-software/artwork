<?php

namespace Artwork\Modules\Shift\Http\Controllers;

use Artwork\Modules\Shift\Exports\ShiftHistoryExcelExport;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Serializers\ShiftHistorySerializer;
use Artwork\Modules\Shift\Services\ShiftHistoryQueryService;
use Artwork\Modules\Shift\Support\ShiftHistoryDayEntryPerson;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ShiftHistoryController
{
    public function __construct(
        private readonly ShiftHistorySerializer $serializer,
        private readonly ShiftHistoryQueryService $queryService,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $filters = $this->queryService->resolveFilters($request->query());
        $shiftId = $filters['shift_id'];

        $perPage = (int) $request->query('per_page', 50);
        $perPage = max(1, min(200, $perPage));

        $page = max(1, (int) $request->query('page', 1));

        $shiftQuery = $this->queryService->shiftQuery($filters);
        // IDs aller Schichten im Zeitraum (ohne Einzelschicht-Filter), daraus die gefilterten
        $rangeShiftIds = (clone $shiftQuery)->pluck('id')->map(fn ($id) => (int) $id)->all();
        $shiftIds = $shiftId > 0
            ? array_values(array_intersect($rangeShiftIds, [$shiftId]))
            : $rangeShiftIds;

        $matchedShiftIds = $this->queryService->matchedShiftIds($filters, $shiftIds);

        // Personenfilter (optional): Schichten der Person + Bezugsgründe pro Eintrag ("warum sehe ich das")
        $person = $this->queryService->resolvePerson($filters);
        $personShiftIds = $person ? $this->queryService->personShiftIds($person, $matchedShiftIds) : [];

        $paginator = $this->queryService
            ->activityQuery($filters, $matchedShiftIds, $person, $personShiftIds)
            ->paginate($perPage);

        // Schichtliste (Auswahlfeld „Schicht“, Beschriftungen) nur auf der ersten Seite und nur mit Schichten,
        // zu denen es im Filter Einträge gibt: vorher kam jede Schicht des Zeitraums mit (Jahr: >10.000)
        $shifts = $page === 1
            ? $this->shiftsWithEntries($filters, $rangeShiftIds, $matchedShiftIds, $person, $personShiftIds)
            : null;

        $items = collect($paginator->items())->map(function ($log) use ($person, $personShiftIds) {
            // Der Verlauf zeigt nur den Namen der handelnden Person — kein volles User-Modell
            // (E-Mail, Stundenkonto, Einstellungen) je Eintrag ausliefern
            $log->causer?->setVisible(['id', 'first_name', 'last_name', 'full_name']);
            $log->setAttribute(
                'match_reasons',
                $person ? $this->queryService->matchReasons($log, $person, $personShiftIds) : []
            );
            // Tageseinträge: betroffene Person (für den Verlauf ohne Personenfilter)
            if (ShiftHistoryQueryService::isDayEntryLog($log)) {
                $properties = $log->getAttribute('properties');
                $log->setAttribute('day_entry_person_name', ShiftHistoryDayEntryPerson::nameFromProperties(
                    $properties instanceof \Illuminate\Support\Collection ? $properties->all() : (array) $properties
                ));
            }

            return $log;
        })->values();

        $response = [
            'logs'   => [
                'data' => $items,
                'meta' => [
                    'current_page' => $paginator->currentPage(),
                    'last_page'    => $paginator->lastPage(),
                    'per_page'     => $paginator->perPage(),
                    'total'        => $paginator->total(),
                ],
            ],
            'range'  => [
                'start_date' => $filters['start_date']->toDateString(),
                'end_date'   => $filters['end_date']->toDateString(),
            ],
        ];

        // Shift-Liste nur auf der ersten Seite mitschicken
        if ($shifts !== null) {
            $response['shifts'] = $shifts;
        }

        return response()->json($response);
    }

    /**
     * Schichten mit mindestens einem Verlaufseintrag im Filter, schlank serialisiert. Ein Einzelschicht-Filter
     * grenzt die Liste nicht ein (Wechsel zu einer anderen Schicht bleibt möglich); die gewählte Schicht ist immer
     * dabei, damit ihr Hinweis beschriftet werden kann.
     *
     * @param array<string, mixed> $filters
     * @param array<int, int> $rangeShiftIds
     * @param array<int, int> $matchedShiftIds
     * @param array<string, mixed>|null $person
     * @param array<int, int> $personShiftIds
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    private function shiftsWithEntries(
        array $filters,
        array $rangeShiftIds,
        array $matchedShiftIds,
        ?array $person,
        array $personShiftIds
    ): \Illuminate\Support\Collection {
        $shiftId = $filters['shift_id'];
        if ($shiftId > 0) {
            $filters['shift_id'] = 0;
            $matchedShiftIds = $this->queryService->matchedShiftIds($filters, $rangeShiftIds);
            $personShiftIds = $person ? $this->queryService->personShiftIds($person, $matchedShiftIds) : [];
        }

        $ids = $this->queryService
            ->activityQuery($filters, $matchedShiftIds, $person, $personShiftIds)
            ->reorder()
            ->where('activity_log.subject_type', Shift::class)
            ->distinct()
            ->pluck('activity_log.subject_id')
            ->map(fn ($id) => (int) $id)
            ->all();
        if ($shiftId > 0 && in_array($shiftId, $rangeShiftIds, true)) {
            $ids[] = $shiftId;
        }

        if ($ids === []) {
            return collect();
        }

        return Shift::withTrashed()
            ->whereIn('id', array_values(array_unique($ids)))
            ->select([
                'id',
                'craft_id',
                'start_date',
                'end_date',
                'start',
                'end',
                'description',
                'room_id',
                'project_id',
                'is_committed',
                'in_workflow',
                'deleted_at',
            ])
            ->with([
                'room:id,name',
                'project:id,name',
                'craft:id,name,abbreviation',
            ])
            ->orderBy('start_date')
            ->orderBy('start')
            ->get()
            ->map($this->serializer->serializeShift(...))
            ->values();
    }

    /**
     * Excel-Export des Schichtverlaufs mit denselben Filtern wie das Modal (Gewerk, Schicht, Zeitraum,
     * Suche, Sortierung) — gleiche Query-Logik (ShiftHistoryQueryService), gleiche Rechte wie index().
     */
    public function export(Request $request): BinaryFileResponse
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'craftId' => 'nullable|integer|min:0',
            'shiftId' => 'nullable|integer|min:0',
            'search' => 'nullable|string|max:200',
            'sort' => 'nullable|string|in:shift_day',
            'person_type' => 'nullable|string|in:' . implode(',', array_keys(ShiftHistoryQueryService::PERSON_TYPES)),
            'person_id' => 'nullable|integer|min:0',
            'person_scope' => 'nullable|string|in:' . implode(',', ShiftHistoryQueryService::PERSON_SCOPES),
        ]);

        $filters = $this->queryService->resolveFilters($request->query());
        $shiftIds = $this->queryService->shiftQuery($filters)
            ->when($filters['shift_id'] > 0, fn ($query) => $query->whereKey($filters['shift_id']))
            ->pluck('id')
            ->all();
        $matchedShiftIds = $this->queryService->matchedShiftIds($filters, $shiftIds);
        $person = $this->queryService->resolvePerson($filters);
        $personShiftIds = $person ? $this->queryService->personShiftIds($person, $matchedShiftIds) : [];

        $export = new ShiftHistoryExcelExport(
            $this->queryService->activityQuery($filters, $matchedShiftIds, $person, $personShiftIds),
            $matchedShiftIds,
            $request->user()?->language ?? app()->getLocale(),
            $person
                ? fn (Activity $log) => $this->queryService->matchReasons($log, $person, $personShiftIds)
                : null,
            // Ohne Personenfilter nennt die Nachricht eines Tageseintrags die betroffene Person
            $person === null
        );

        return $export->download(sprintf(
            'schichtverlauf_%s_bis_%s.xlsx',
            $filters['start_date']->format('Y-m-d'),
            $filters['end_date']->format('Y-m-d')
        ));
    }
}
