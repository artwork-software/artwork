<?php

namespace Artwork\Modules\Shift\Http\Controllers;

use Artwork\Modules\Shift\Exports\ShiftHistoryExcelExport;
use Artwork\Modules\Shift\Serializers\ShiftHistorySerializer;
use Artwork\Modules\Shift\Services\ShiftHistoryQueryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
        // Die vollständige Shift-Liste (inkl. Relationen) ändert sich beim Paginieren nicht
        // und wird daher nur für die erste Seite geladen & serialisiert. Beim "Mehr laden"
        // brauchen wir nur die IDs, um die passenden Activities zu filtern.
        $loadShiftDetails = $page === 1;

        $shiftQuery = $this->queryService->shiftQuery($filters);

        if ($loadShiftDetails) {
            // Avoid serializing expensive Shift appends for the filter dropdown.
            $shifts = (clone $shiftQuery)
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

            $shiftIds = $shiftId > 0
                ? $shifts->where('id', $shiftId)->pluck('id')->all()
                : $shifts->pluck('id')->all();
        } else {
            // Folgeseiten: nur die IDs, um die Activities zu filtern.
            $shifts = null;
            $shiftIds = (clone $shiftQuery)
                ->when($shiftId > 0, fn ($query) => $query->whereKey($shiftId))
                ->pluck('id')
                ->all();
        }

        $matchedShiftIds = $this->queryService->matchedShiftIds($filters, $shiftIds);

        $paginator = $this->queryService->activityQuery($filters, $matchedShiftIds)->paginate($perPage);

        $response = [
            'logs'   => [
                'data' => $paginator->items(),
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

        // Shift-Liste nur auf der ersten Seite mitschicken (siehe $loadShiftDetails).
        if ($loadShiftDetails) {
            $response['shifts'] = $shifts;
        }

        return response()->json($response);
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
        ]);

        $filters = $this->queryService->resolveFilters($request->query());
        $shiftIds = $this->queryService->shiftQuery($filters)
            ->when($filters['shift_id'] > 0, fn ($query) => $query->whereKey($filters['shift_id']))
            ->pluck('id')
            ->all();
        $matchedShiftIds = $this->queryService->matchedShiftIds($filters, $shiftIds);

        $export = new ShiftHistoryExcelExport(
            $this->queryService->activityQuery($filters, $matchedShiftIds),
            $matchedShiftIds,
            $request->user()?->language ?? app()->getLocale()
        );

        return $export->download(sprintf(
            'schichtverlauf_%s_bis_%s.xlsx',
            $filters['start_date']->format('Y-m-d'),
            $filters['end_date']->format('Y-m-d')
        ));
    }
}
