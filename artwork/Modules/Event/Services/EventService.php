<?php

namespace Artwork\Modules\Event\Services;

use App\Models\Event;
use Artwork\Modules\Event\Repositories\EventRepository;
use Artwork\Modules\PresetShift\Models\PresetShift;
use Artwork\Modules\PresetShift\Models\PresetShiftShiftsQualifications;
use Artwork\Modules\Shift\Services\ShiftService;
use Artwork\Modules\Shift\Services\ShiftsQualificationsService;
use Artwork\Modules\ShiftPreset\Models\ShiftPreset;
use Artwork\Modules\ShiftQualification\Services\ShiftQualificationService;
use Artwork\Modules\Timeline\Services\TimelineService;

class EventService
{
    public function __construct(
        private readonly EventRepository $eventRepository,
        private readonly ShiftService $shiftService,
        private readonly ShiftsQualificationsService $shiftsQualificationsService,
        private readonly ShiftQualificationService $shiftQualificationService,
        private readonly TimelineService $timelineService
    ) {
    }

    public function importShiftPreset(Event $event, ShiftPreset $shiftPreset): void
    {
        $this->timelineService->deleteTimelines($event->timeline);
        foreach ($shiftPreset->timeline as $shiftPresetTimeline) {
            $this->timelineService->createFromShiftPresetTimeline($shiftPresetTimeline, $event->id);
        }

        $this->shiftService->deleteShifts($event->shifts);
        /** @var PresetShift $presetShift */
        foreach ($shiftPreset->shifts as $presetShift) {
            $shift = $this->shiftService->createFromShiftPresetShiftForEvent($presetShift, $event->id);

            /** @var PresetShiftShiftsQualifications $presetShiftShiftsQualification */
            foreach ($presetShift->shiftsQualifications as $presetShiftShiftsQualification) {
                if (
                    !$this->shiftQualificationService->isStillAvailable(
                        $presetShiftShiftsQualification->shift_qualification_id
                    )
                ) {
                    continue;
                }

                $this->shiftsQualificationsService->createShiftsQualificationForShift(
                    $shift->id,
                    [
                        'shift_qualification_id' => $presetShiftShiftsQualification->shift_qualification_id,
                        'value' => $presetShiftShiftsQualification->value
                    ]
                );
            }
        }
    }

    public function importShiftPresetForEventsOfProjectByEventType(
        ShiftPreset $shiftPreset,
        int $projectId
    ): void {
        foreach (
            $this->eventRepository->getEventsByProjectIdAndEventTypeId(
                $projectId,
                $shiftPreset->event_type_id
            ) as $eventByProjectIdAndEventTypeId
        ) {
            $this->importShiftPreset($eventByProjectIdAndEventTypeId, $shiftPreset);
        }
    }
}
