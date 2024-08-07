<?php

namespace Artwork\Modules\Event\Repositories;

use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Project\Models\Project;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class EventRepository extends BaseRepository
{
    public function getEventsByProjectIdAndEventTypeId(int $projectId, int $eventTypeId): Collection
    {
        return Event::byProjectId($projectId)->byEventTypeId($eventTypeId)->get();
    }

    public function getEventsWhereUserHasShifts(int $userId): Collection
    {
        return Event::query()
            ->with(['shifts', 'shifts.shiftsQualifications'])
            ->whereHas(
                'shifts.users',
                function (Builder $builder) use ($userId): void {
                    $builder->where('user_id', $userId);
                }
            )
            ->get();
    }

    public function getAll(): Collection
    {
        return Event::all();
    }

    public function getEventsWhereFreelancerHasShifts(int $freelancerId): Collection
    {
        return Event::query()
            ->with(['shifts', 'shifts.shiftsQualifications'])
            ->whereHas(
                'shifts.freelancer',
                function (Builder $builder) use ($freelancerId): void {
                    $builder->where('freelancer_id', $freelancerId);
                }
            )
            ->get();
    }

    public function getEventsWhereServiceProviderHasShifts(int $serviceProviderId): Collection
    {
        return Event::query()
            ->with(['shifts', 'shifts.shiftsQualifications'])
            ->whereHas(
                'shifts.serviceProvider',
                function (Builder $builder) use ($serviceProviderId): void {
                    $builder->where('service_provider_id', $serviceProviderId);
                }
            )
            ->get();
    }

    public function getEventsWhereHasShiftsStartAndEndTimeOverlap(Carbon $startDate, Carbon $endDate): Collection
    {
        return Event::with(['shifts', 'event_type', 'room'])
            ->whereHas(
                'shifts',
                function (Builder $builder): void {
                    $builder->whereNotNull('shifts.id')->without('crafts');
                }
            )
            ->startAndEndTimeOverlap($startDate, $endDate)
            ->without(['series'])
            ->get();
    }

    public function findById(int $id): Event
    {
        return Event::find($id);
    }

    public function getEventsWithoutRoom(int|Project $project = null, ?array $with = null): Collection
    {
        /** @var Builder $builder */
        $builder = Event::query()->hasNoRoom();

        if ($project) {
            $builder->byProjectId(($project instanceof Project) ? $project->getAttribute('id') : $project);
        }

        if ($with) {
            $builder->with($with);
        }

        return $builder->get();
    }
}
