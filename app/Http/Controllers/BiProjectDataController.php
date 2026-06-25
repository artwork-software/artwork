<?php

namespace App\Http\Controllers;

use Artwork\Modules\BusinessIntelligence\Enums\BiVisitorModeEnum;
use Artwork\Modules\BusinessIntelligence\Http\Requests\UpdateBiEventDataRequest;
use Artwork\Modules\BusinessIntelligence\Http\Requests\UpdateBiProjectDataRequest;
use Artwork\Modules\BusinessIntelligence\Services\BiDerivedValuesService;
use Artwork\Modules\BusinessIntelligence\Services\BiProjectDataService;
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
        private readonly BiDerivedValuesService $biDerivedValuesService
    ) {
    }

    public function show(Project $project): JsonResponse
    {
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

        return response()->json([
            'bi_data' => $biData,
            'event_data' => $eventData,
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
        $biData = $this->biProjectDataService->updateData($project->id, $request->validated());

        return response()->json($biData);
    }

    public function switchVisitorMode(Request $request, Project $project): JsonResponse
    {
        $request->validate(['mode' => ['required', 'string', 'in:total,per_event']]);
        $biData = $this->biProjectDataService->switchVisitorMode(
            $project->id,
            BiVisitorModeEnum::from($request->input('mode'))
        );

        return response()->json($biData);
    }

    public function switchSoldTicketsMode(Request $request, Project $project): JsonResponse
    {
        $request->validate(['mode' => ['required', 'string', 'in:total,per_event']]);
        $biData = $this->biProjectDataService->switchSoldTicketsMode(
            $project->id,
            BiVisitorModeEnum::from($request->input('mode'))
        );

        return response()->json($biData);
    }

    public function switchRevenueMode(Request $request, Project $project): JsonResponse
    {
        $request->validate(['mode' => ['required', 'string', 'in:total,per_event']]);
        $biData = $this->biProjectDataService->switchRevenueMode(
            $project->id,
            BiVisitorModeEnum::from($request->input('mode'))
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
            $request->validated()
        );

        return response()->json($eventData);
    }

    public function roomCapacities(Project $project): JsonResponse
    {
        return response()->json($this->biProjectDataService->getRoomCapacities($project->id));
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
