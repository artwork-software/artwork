<?php

namespace Artwork\Modules\Shift\Repositories;

use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftFreelancer;
use Artwork\Modules\Shift\Models\ShiftServiceProvider;
use Artwork\Modules\Shift\Models\ShiftUser;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

readonly class ShiftRepository extends BaseRepository
{
    public function getById(int $shiftId): Shift|null
    {
        return Shift::find($shiftId);
    }

    public function getShiftsByUuid(string $shiftUuid): Collection
    {
        return Shift::allByUuid($shiftUuid)->get();
    }

    public function getShiftUserPivotById(Shift $shift, int $userId): ShiftUser|null
    {
        return $shift->users()->where('users.id', $userId)->first()?->pivot;
    }

    public function getShiftFreelancerPivotById(Shift $shift, int $freelancerId): ShiftFreelancer|null
    {
        return $shift->freelancer()->where('freelancers.id', $freelancerId)->first()?->pivot;
    }

    public function getShiftServiceProviderPivotById(Shift $shift, int $serviceProviderId): ShiftServiceProvider|null
    {
        return $shift->serviceProvider()->where('service_providers.id', $serviceProviderId)->first()?->pivot;
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
}
