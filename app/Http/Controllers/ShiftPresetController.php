<?php

namespace App\Http\Controllers;

use Artwork\Modules\Craft\Services\CraftService;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Event\Services\EventService;
use Artwork\Modules\EventType\Services\EventTypeService;
use Artwork\Modules\PresetShift\Services\PresetShiftService;
use Artwork\Modules\PresetShift\Services\PresetShiftsShiftsQualificationsService;
use Artwork\Modules\Shift\Services\ShiftService;
use Artwork\Modules\Shift\Services\ShiftsQualificationsService;
use Artwork\Modules\ShiftPreset\Models\ShiftPreset;
use Artwork\Modules\ShiftPreset\Services\ShiftPresetService;
use Artwork\Modules\ShiftPresetTimeline\Services\ShiftPresetTimelineService;
use Artwork\Modules\ShiftQualification\Services\ShiftQualificationService;
use Artwork\Modules\Timeline\Services\TimelineService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ShiftPresetController extends Controller
{
    public function __construct(
        private readonly CraftService $craftService,
        private readonly EventTypeService $eventTypeService,
        private readonly ShiftQualificationService $shiftQualificationService,
        private readonly ShiftPresetService $shiftPresetService,
        private readonly EventService $eventService,
        private readonly PresetShiftService $presetShiftService,
        private readonly PresetShiftsShiftsQualificationsService $presetShiftsShiftsQualificationsService,
        private readonly ShiftPresetTimelineService $shiftPresetTimelineService
    ) {
    }

    public function index(): Response
    {
        return Inertia::render('Shifts/ShiftPresets', [
            'shiftPresets' => $this->shiftPresetService->getAllShiftPresetsWithSortedTimelines(),
            'shiftQualifications' => $this->shiftQualificationService->getAllOrderedByCreationDateAscending(),
            'crafts' => $this->craftService->getAll(),
            'event_types' => $this->eventTypeService->getAll()
        ]);
    }

    public function store(Request $request, Event $event): void
    {
        $this->shiftPresetService->storeFromEventAndRequest(
            $event,
            $request,
            $this->presetShiftService,
            $this->presetShiftsShiftsQualificationsService,
            $this->shiftPresetTimelineService
        );
    }

    public function duplicate(ShiftPreset $shiftPreset): void
    {
        $this->shiftPresetService->duplicateShiftPreset(
            $shiftPreset,
            $this->presetShiftService,
            $this->presetShiftsShiftsQualificationsService,
            $this->shiftPresetTimelineService
        );
    }

    public function update(Request $request, ShiftPreset $shiftPreset): void
    {
        $this->shiftPresetService->updateFromRequest($shiftPreset, $request);
    }

    public function destroy(ShiftPreset $shiftPreset): void
    {
        $this->shiftPresetService->delete($shiftPreset);
    }

    public function storeEmpty(Request $request): void
    {
        $this->shiftPresetService->createFromRequest($request);
    }

    public function search(Request $request): Collection
    {
        return $this->shiftPresetService->findByNameAndEventTypeId(
            $request->get('query'),
            $request->get('eventTypeId')
        );
    }

    public function import(
        Request $request,
        Event $event,
        ShiftPreset $shiftPreset,
        TimelineService $timelineService,
        ShiftService $shiftService,
        ShiftQualificationService $shiftQualificationService,
        ShiftsQualificationsService $shiftsQualificationsService
    ): void {
        if (!$request->boolean('all')) {
            $this->eventService->importShiftPreset(
                $event,
                $shiftPreset,
                $timelineService,
                $shiftService,
                $shiftQualificationService,
                $shiftsQualificationsService
            );
            return;
        }

        $this->eventService->importShiftPresetForEventsOfProjectByEventType(
            $shiftPreset,
            $event->project_id,
            $timelineService,
            $shiftService,
            $shiftQualificationService,
            $shiftsQualificationsService
        );
    }
}
