<?php

namespace Artwork\Modules\Shift\Repositories;

use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftWorker;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

class ShiftRepository extends BaseRepository
{
    public function getAll(array $with = []): Collection
    {
        return Shift::query()->with($with)->get();
    }

    public function getById(int $shiftId): Shift|null
    {
        return Shift::find($shiftId);
    }

    public function getShiftsByUuid(string $shiftUuid): Collection
    {
        return Shift::allByUuid($shiftUuid)->get();
    }

    public function getShiftWorkerPivotById(
        Shift $shift,
        string $employableType,
        int $employableId
    ): ShiftWorker|null {
        return ShiftWorker::query()
            ->where('shift_id', $shift->id)
            ->where('employable_type', $employableType)
            ->where('employable_id', $employableId)
            ->first();
    }

    public function getShiftsByUuidBetweenDates(string $shiftUuid, Carbon $startDate, Carbon $endDate): Collection
    {
        return Shift::allByUuid($shiftUuid)
            ->eventStartDayAndEventEndDayBetween($startDate, $endDate)
            ->get();
    }

    public function getShiftsBetweenEventStartDayAndEventEndDayStartAndEndTimeOverlapByProjectEventIds(
        Carbon $eventStartDay,
        Carbon $eventEndDay,
        string $shiftStart,
        string $shiftEnd,
        array $projectEventIds
    ): Collection {
        return Shift::eventStartDayAndEventEndDayBetween($eventStartDay, $eventEndDay)
            ->startAndEndOverlap($shiftStart, $shiftEnd)
            ->eventIdInArray($projectEventIds)
            ->get();
    }

    public function getShiftsBetweenDates(
        Carbon $eventStartDay,
        Carbon $eventEndDay,
        array $with = []
    ): Collection {
        return Shift::eventStartDayAndEventEndDayBetween($eventStartDay, $eventEndDay)->with($with)->get();
    }
}
