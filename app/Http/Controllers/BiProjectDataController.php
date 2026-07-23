<?php

namespace App\Http\Controllers;

use Artwork\Modules\BusinessIntelligence\Enums\BiVisitorModeEnum;
use Artwork\Modules\BusinessIntelligence\Models\BiAudienceCategory;
use Artwork\Modules\BusinessIntelligence\Http\Requests\UpdateBiEventDataRequest;
use Artwork\Modules\BusinessIntelligence\Http\Requests\UpdateBiProjectDataRequest;
use Artwork\Modules\BusinessIntelligence\Services\BiDerivedValuesService;
use Artwork\Modules\BusinessIntelligence\Services\BiProjectDataService;
use Artwork\Modules\BusinessIntelligence\Services\BiProjectMetricsService;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Project\Models\Component;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ProjectComponentValue;
use Artwork\Modules\Room\Models\Room;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BiProjectDataController extends Controller
{
    public function __construct(
        private readonly BiProjectDataService $biProjectDataService,
        private readonly BiDerivedValuesService $biDerivedValuesService,
        private readonly BiProjectMetricsService $biProjectMetricsService
    ) {
    }

    /**
     * Scope aus dem Request (Plan/Ist-Dimension); alles außer 'plan' ist Ist.
     */
    private function scopeFrom(Request $request): string
    {
        return $request->input('scope') === 'plan' ? 'plan' : 'actual';
    }

    public function show(Project $project): JsonResponse
    {
        $project->load([
            'biData',
            'biEventData.event',
            'planBiData',
            'planBiEventData.event',
            'biRoomCapacities',
            'biAudienceCategoryValues',
            'events.room',
            'events.event_type.biTags',
        ]);

        $biData = $this->biProjectDataService->getOrCreateForProject($project->id);
        $eventData = $this->biProjectDataService->getEventData($project->id);
        $roomCapacities = $this->biProjectDataService->getRoomCapacities($project->id);
        $derivedValues = $this->biDerivedValuesService->getDerivedValues($project);
        $tagCounts = $this->biDerivedValuesService->getTagBasedCounts($project);
        $timeEfforts = $project->biTimeEfforts()->with('user')->get();
        $snapshots = $project->biSnapshots()->with('creator')->orderByDesc('snapshot_date')->get();

        $projectEvents = $project->events()
            ->with(['room', 'event_type'])
            ->orderBy('start_time')
            ->get()
            ->map(function (Event $event) {
                return [
                    'id' => $event->id,
                    'name' => $event->eventName ?? $event->event_type?->name ?? '',
                    'start_time' => $event->start_time?->format('d.m.Y H:i'),
                    'end_time' => $event->end_time?->format('d.m.Y H:i'),
                    'room_name' => $event->room?->name ?? '',
                    'room_id' => $event->room_id,
                    'event_type' => $event->event_type?->name ?? '',
                ];
            });

        $projectRooms = $project->events()
            ->with('room')
            ->get()
            ->pluck('room')
            ->filter()
            ->unique('id')
            ->map(function (Room $room) {
                return [
                    'id' => $room->id,
                    'name' => $room->name,
                    'default_capacity' => $room->capacity,
                ];
            })
            ->values();

        $biCustomFields = Component::isBiField()
            ->select(['id', 'name', 'type', 'data', 'is_bi_field', 'bi_order'])
            ->orderBy('bi_order')
            ->get();

        $biCustomFieldValues = ProjectComponentValue::where('project_id', $project->id)
            ->whereIn('component_id', $biCustomFields->pluck('id'))
            ->get()
            ->keyBy('component_id');

        $period = $project->events()
            ->selectRaw('MIN(start_time) as min_start, MAX(end_time) as max_end')
            ->first();
        $projectPeriod = ($period && $period->min_start)
            ? [
                'from' => Carbon::parse($period->min_start)->toDateString(),
                'to' => Carbon::parse($period->max_end ?? $period->min_start)->toDateString(),
            ]
            : null;

        // Plan-Datensatz nur lesen, nie hier anlegen — der Plan-Modus im Frontend
        // startet leer und legt die Zeile erst beim ersten Schreiben an.
        $planComparison = $this->biProjectMetricsService->planComparison($project);

        return response()->json([
            'metrics_summary' => $this->biProjectMetricsService->summary($project),
            'bi_data' => $biData,
            'event_data' => $eventData,
            'plan_data' => $project->planBiData,
            'plan_event_data' => $this->biProjectDataService->getEventData($project->id, 'plan'),
            'plan_metrics_summary' => $planComparison['has_plan']
                ? $this->biProjectMetricsService->forScope('plan')->summary($project)
                : null,
            'plan_comparison' => $planComparison,
            'budget_suggestions' => array_merge(
                $this->biDerivedValuesService->getRevenueSuggestions($project),
                $this->biDerivedValuesService->getCostSuggestions($project)
            ),
            'audience_categories' => BiAudienceCategory::ordered()->get(),
            'audience_category_values' => $this->biProjectDataService->getCategoryValues($project->id),
            'plan_audience_category_values' => $this->biProjectDataService->getCategoryValues($project->id, 'plan'),
            'room_capacities' => $roomCapacities,
            'derived_values' => $derivedValues,
            'tag_counts' => $tagCounts,
            'time_efforts' => $timeEfforts,
            'snapshots' => $snapshots,
            'project_events' => $projectEvents,
            'project_rooms' => $projectRooms,
            'bi_custom_fields' => $biCustomFields,
            'bi_custom_field_values' => $biCustomFieldValues,
            'project_period' => $projectPeriod,
        ]);
    }

    public function updateData(UpdateBiProjectDataRequest $request, Project $project): JsonResponse
    {
        $data = collect($request->validated())->except('scope')->all();

        // Herkunfts-Stempel: gesetzte Quelle bekommt einen Zeitpunkt, manuelles
        // Überschreiben des Umsatzes/der Kosten löscht die Herkunft wieder.
        if (array_key_exists('revenue_source', $data)) {
            $data['revenue_source_synced_at'] = $data['revenue_source'] !== null ? now() : null;
        } elseif (array_key_exists('revenue_total', $data)) {
            $data['revenue_source'] = null;
            $data['revenue_source_synced_at'] = null;
        }

        if (array_key_exists('costs_source', $data)) {
            $data['costs_source_synced_at'] = $data['costs_source'] !== null ? now() : null;
        } elseif (array_key_exists('costs_total', $data)) {
            $data['costs_source'] = null;
            $data['costs_source_synced_at'] = null;
        }

        $biData = $this->biProjectDataService->updateData(
            $project->id,
            $data,
            $this->scopeFrom($request)
        );

        return response()->json($biData);
    }

    public function switchVisitorMode(Request $request, Project $project): JsonResponse
    {
        $request->validate(['mode' => ['required', 'string', 'in:total,per_event']]);
        $biData = $this->biProjectDataService->switchVisitorMode(
            $project->id,
            BiVisitorModeEnum::from($request->input('mode')),
            $this->scopeFrom($request)
        );

        return response()->json($biData);
    }

    public function switchSoldTicketsMode(Request $request, Project $project): JsonResponse
    {
        $request->validate(['mode' => ['required', 'string', 'in:total,per_event']]);
        $biData = $this->biProjectDataService->switchSoldTicketsMode(
            $project->id,
            BiVisitorModeEnum::from($request->input('mode')),
            $this->scopeFrom($request)
        );

        return response()->json($biData);
    }

    public function switchRevenueMode(Request $request, Project $project): JsonResponse
    {
        $request->validate(['mode' => ['required', 'string', 'in:total,per_event']]);
        $biData = $this->biProjectDataService->switchRevenueMode(
            $project->id,
            BiVisitorModeEnum::from($request->input('mode')),
            $this->scopeFrom($request)
        );

        return response()->json($biData);
    }

    public function upsertEventData(
        UpdateBiEventDataRequest $request,
        Project $project,
        Event $event
    ): JsonResponse {
        // IDOR-Schutz: das Event muss zum Projekt aus der URL gehören.
        abort_unless((int) $event->project_id === (int) $project->id, 404);

        $eventData = $this->biProjectDataService->upsertEventData(
            $project->id,
            $event->id,
            collect($request->validated())->except('scope')->all(),
            $this->scopeFrom($request)
        );

        return response()->json($eventData);
    }

    public function roomCapacities(Project $project): JsonResponse
    {
        return response()->json($this->biProjectDataService->getRoomCapacities($project->id));
    }

    /**
     * Kennzahlen-Summary für einen frei wählbaren Zeitraum — Grundlage des
     * Zeitraumvergleichs im Projekt-Tab (zwei Aufrufe: Zeitraum A und B).
     */
    public function metricsSummary(Request $request, Project $project): JsonResponse
    {
        $validated = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);

        $project->load([
            'biData',
            'biEventData.event',
            'biRoomCapacities',
            'biAudienceCategoryValues',
            'events.room',
            'events.event_type.biTags',
        ]);

        $from = isset($validated['date_from']) ? Carbon::parse($validated['date_from']) : null;
        $to = isset($validated['date_to']) ? Carbon::parse($validated['date_to']) : null;

        return response()->json([
            'summary' => $this->biProjectMetricsService->summary($project, $from, $to),
            'range' => [
                'from' => $from?->toDateString(),
                'to' => $to?->toDateString(),
            ],
        ]);
    }

    public function initializePlan(Request $request, Project $project): JsonResponse
    {
        $validated = $request->validate([
            'mode' => ['required', 'in:empty,copy_actual_structure,copy_project'],
            'source_project_id' => ['required_if:mode,copy_project', 'nullable', 'integer', 'exists:projects,id'],
        ]);

        $sourceProject = null;
        if ($validated['mode'] === 'copy_project') {
            $sourceProject = Project::findOrFail((int) $validated['source_project_id']);
            // Werte-Kopie ist eine Lesung des Quellprojekts → Sichtrecht nötig
            $this->authorize('view', $sourceProject);
        }

        $plan = $this->biProjectDataService->initializePlan($project->id, $validated['mode'], $sourceProject);

        return response()->json($plan);
    }

    public function upsertCategoryValues(Request $request, Project $project): JsonResponse
    {
        $validated = $request->validate([
            'values' => ['required', 'array'],
            'values.*.bi_audience_category_id' => ['required', 'integer', 'exists:bi_audience_categories,id'],
            'values.*.event_id' => ['nullable', 'integer'],
            'values.*.quantity' => ['nullable', 'integer', 'min:0', 'max:100000000'],
        ]);

        // IDOR-Schutz: Termin-Werte nur für Termine dieses Projekts.
        $projectEventIds = $project->events()->pluck('id')->flip();
        foreach ($validated['values'] as $entry) {
            if (($entry['event_id'] ?? null) !== null && !$projectEventIds->has((int) $entry['event_id'])) {
                abort(404);
            }
        }

        $values = $this->biProjectDataService->upsertCategoryValues(
            $project->id,
            $validated['values'],
            $this->scopeFrom($request)
        );

        return response()->json($values);
    }

    public function updateRoomCapacity(Request $request, Project $project, Room $room): JsonResponse
    {
        $request->validate(['capacity_override' => ['nullable', 'integer', 'min:0']]);

        $capacity = $this->biProjectDataService->updateRoomCapacity(
            $project->id,
            $room->id,
            $request->input('capacity_override')
        );

        return response()->json($capacity);
    }
}
