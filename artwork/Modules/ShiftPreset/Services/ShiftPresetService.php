<?php

namespace Artwork\Modules\ShiftPreset\Services;

use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\PresetShift\Services\PresetShiftService;
use Artwork\Modules\PresetShift\Services\PresetShiftsShiftsQualificationsService;
use Artwork\Modules\ShiftPreset\Models\ShiftPreset;
use Artwork\Modules\ShiftPreset\Repositories\ShiftPresetRepository;
use Artwork\Modules\ShiftPresetTimeline\Services\ShiftPresetTimelineService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

class ShiftPresetService
{
    public function __construct(
        private readonly ShiftPresetRepository $shiftPresetRepository,
        private readonly PresetShiftService $presetShiftService,
        private readonly PresetShiftsShiftsQualificationsService $presetShiftsShiftsQualificationsService,
        private readonly ShiftPresetTimelineService $shiftPresetTimelineService
    ) {
    }

    public function getAllShiftPresetsWithSortedTimelines(): Collection
    {
        $shiftPresets = $this->shiftPresetRepository->getAllWithEventTypesShiftsAndTimeline();

        /** @var ShiftPreset $shiftPreset */
        foreach ($shiftPresets as $shiftPreset) {
            $presetTimelines = $shiftPreset->timeline->toArray();

            usort($presetTimelines, function ($a, $b) {
                if ($a['start'] === null && $b['start'] === null) {
                    return 0;
                } elseif ($a['start'] === null) {
                    return 1; // $a should come later in the array
                } elseif ($b['start'] === null) {
                    return -1; // $b should come later in the array
                }

                // Compare the 'start' values for ascending order
                return strtotime($a['start']) - strtotime($b['start']);
            });

            foreach ($presetTimelines as &$presetTimeline) {
                $presetTimeline['description_without_html'] = strip_tags($presetTimeline['description']);
            }

            $shiftPreset->setRelation('timeline', collect($presetTimelines));
        }

        return $shiftPresets;
    }

    public function findByNameAndEventTypeId(string $name, int $eventTypeId): Collection
    {
        return $this->shiftPresetRepository->findByNameAndEventTypeId($name, $eventTypeId);
    }

    public function storeFromEventAndRequest(Event $event, Request $request): void
    {
        $shiftPreset = $this->createFromRequest($request);

        foreach ($event->shifts as $shift) {
            $presetShift = $this->presetShiftService->createPresetShiftFromExistingShift($shiftPreset->id, $shift);

            foreach ($shift->shiftsQualifications as $shiftsQualifications) {
                $this->presetShiftsShiftsQualificationsService->createShiftsQualificationsForPresetShift(
                    $presetShift->id,
                    [
                        'shift_qualification_id' => $shiftsQualifications->shift_qualification_id,
                        'value' => $shiftsQualifications->value
                    ]
                );
            }
        }

        foreach ($event->timeline as $timeline) {
            $this->shiftPresetTimelineService->createFromExistingTimeline($shiftPreset->id, $timeline);
        }
    }

    public function duplicateShiftPreset(ShiftPreset $shiftPreset): void
    {
        $duplicatedShiftPreset = $this->createFromExistingShiftPreset($shiftPreset);

        foreach ($shiftPreset->shifts as $presetShift) {
            $duplicatedPresetShift = $this->presetShiftService->createPresetShiftFromExistingPresetShift(
                $duplicatedShiftPreset->id,
                $presetShift
            );

            foreach ($presetShift->shiftsQualifications as $presetShiftShiftsQualification) {
                $this->presetShiftsShiftsQualificationsService->createShiftsQualificationsForPresetShift(
                    $duplicatedPresetShift->id,
                    [
                        'shift_qualification_id' => $presetShiftShiftsQualification->shift_qualification_id,
                        'value' => $presetShiftShiftsQualification->value
                    ]
                );
            }
        }

        foreach ($shiftPreset->timeline as $timeline) {
            $this->shiftPresetTimelineService->createFromExistingPresetTimeline($duplicatedShiftPreset->id, $timeline);
        }
    }

    public function createFromRequest(Request $request): ShiftPreset
    {
        $shiftPreset = new ShiftPreset([
            'name' => $request->get('name'),
            'event_type_id' => $request->get('event_type_id')
        ]);
        $this->shiftPresetRepository->save($shiftPreset);

        return $shiftPreset;
    }

    public function updateFromRequest(ShiftPreset $shiftPreset, Request $request): ShiftPreset
    {
        $shiftPreset->fill($request->only(['name', 'event_type_id']));

        $this->shiftPresetRepository->save($shiftPreset);

        return $shiftPreset;
    }

    private function createFromExistingShiftPreset(ShiftPreset $shiftPreset): ShiftPreset
    {
        $duplicatedShiftPreset = new ShiftPreset([
            'name' => $shiftPreset->name,
            'event_type_id' => $shiftPreset->event_type_id
        ]);

        $this->shiftPresetRepository->save($duplicatedShiftPreset);

        return $duplicatedShiftPreset;
    }

    public function delete(ShiftPreset $shiftPreset): void
    {
        $this->shiftPresetRepository->delete($shiftPreset);
    }
}
