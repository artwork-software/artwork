<?php

namespace Artwork\Modules\Event\Repositories;

use Artwork\Core\Database\Models\CanSubstituteBaseModel;
use Artwork\Core\Database\Models\Model;
use Artwork\Core\Database\Models\Pivot;
use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Project\Models\Project;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Query\Builder as BaseBuilder;
use Illuminate\Notifications\DatabaseNotification;

class EventRepository extends BaseRepository
{
    public function __construct(private readonly Event $event)
    {
    }

    public function getNewModelInstance(): Model|Pivot|DatabaseNotification|CanSubstituteBaseModel
    {
         return $this->event->newInstance();
    }

    public function getNewModelQuery(): BaseBuilder|Builder
    {
        return $this->event->newModelQuery();
    }

    public function getEventsByProjectIdAndEventTypeId(int $projectId, int $eventTypeId): Collection
    {
        return Event::byProjectId($projectId)->byEventTypeId($eventTypeId)->get();
    }

    public function getEventsWhereUserHasShiftsInPeriod(int $userId, CarbonPeriod $carbonPeriod): Collection
    {
        return Event::query()
            ->with(
                [
                    'room',
                    'shifts' => function (HasMany $query) use ($userId): void {
                        $query->whereRelation('users', 'user_id', $userId);
                        $query->orderBy('start_date');
                        $query->orderBy('start');
                        $query->orderBy('end_date');
                        $query->orderBy('end');
                    },
                    'shifts.users',
                    'shifts.users.dayServices',
                    'shifts.freelancer',
                    'shifts.serviceProvider',
                    'shifts.shiftsQualifications',
                ]
            )
            ->whereHas(
                'shifts.users',
                function (Builder $builder) use ($userId): void {
                    $builder->where('user_id', $userId);
                }
            )
            ->whereBetween('start_time', $carbonPeriod)
            ->whereBetween('end_time', $carbonPeriod)
            ->orderBy('start_time')
            ->orderBy('end_time')
            ->get();
    }

    public function getAll(): Collection
    {
        return Event::all();
    }

    public function getEventsWhereFreelancerHasShiftsInPeriod(
        int $freelancerId,
        CarbonPeriod $carbonPeriod
    ): Collection {
        return Event::query()
            ->with(
                [
                    'room',
                    'shifts' => function (HasMany $query) use ($freelancerId): void {
                        $query->whereRelation('freelancer', 'freelancer_id', $freelancerId);
                        $query->orderBy('start_date');
                        $query->orderBy('start');
                        $query->orderBy('end_date');
                        $query->orderBy('end');
                    },
                    'shifts.users',
                    'shifts.users.dayServices',
                    'shifts.freelancer',
                    'shifts.serviceProvider',
                    'shifts.shiftsQualifications',
                ]
            )
            ->whereHas(
                'shifts.freelancer',
                function (Builder $builder) use ($freelancerId): void {
                    $builder->where('freelancer_id', $freelancerId);
                }
            )
            ->whereBetween('start_time', $carbonPeriod)
            ->whereBetween('end_time', $carbonPeriod)
            ->orderBy('start_time')
            ->orderBy('end_time')
            ->get();
    }

    public function getEventsWhereServiceProviderHasShiftsInPeriod(
        int $serviceProviderId,
        CarbonPeriod $carbonPeriod
    ): Collection {
        return Event::query()
            ->with(
                [
                    'room',
                    'shifts' => function (HasMany $query) use ($serviceProviderId): void {
                        $query->whereRelation(
                            'serviceProvider',
                            'service_provider_id',
                            $serviceProviderId
                        );
                        $query->orderBy('start_date');
                        $query->orderBy('start');
                        $query->orderBy('end_date');
                        $query->orderBy('end');
                    },
                    'shifts.users',
                    'shifts.users.dayServices',
                    'shifts.freelancer',
                    'shifts.serviceProvider',
                    'shifts.shiftsQualifications',
                ]
            )
            ->whereHas(
                'shifts.serviceProvider',
                function (Builder $builder) use ($serviceProviderId): void {
                    $builder->where('service_provider_id', $serviceProviderId);
                }
            )
            ->whereBetween('start_time', $carbonPeriod)
            ->whereBetween('end_time', $carbonPeriod)
            ->orderBy('start_time')
            ->orderBy('end_time')
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

    public function findById(int $id): ?Event
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

    public function getOrderBySubQueryBuilder(string $column, string $direction): Builder
    {
        return $this->getNewModelQuery()->getOrderBySubQueryBuilder($column, $direction);
    }
}
