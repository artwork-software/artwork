<?php

namespace Artwork\Modules\ServiceProvider\Services;

use Artwork\Modules\Event\Services\EventService;
use Artwork\Modules\EventType\Services\EventTypeService;
use Artwork\Modules\Project\Services\ProjectService;
use Artwork\Modules\Project\Enum\ProjectTabComponentEnum;
use Artwork\Modules\Project\Services\ProjectTabService;
use Artwork\Modules\Room\Services\RoomService;
use Artwork\Modules\ServiceProvider\DTOs\ShowDto;
use Artwork\Modules\ServiceProvider\Http\Resources\ServiceProviderShiftPlanResource;
use Artwork\Modules\ServiceProvider\Http\Resources\ServiceProviderShowResource;
use Artwork\Modules\ServiceProvider\Models\ServiceProvider;
use Artwork\Modules\ServiceProvider\Repositories\ServiceProviderRepository;
use Artwork\Modules\Shift\Services\ShiftQualificationService;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Services\UserService;
use Artwork\Modules\User\Services\WorkingHourService;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;

readonly class ServiceProviderService
{
    public function __construct(
        private ServiceProviderRepository $serviceProviderRepository,
        private ProjectTabService $projectTabService,
        private WorkingHourService $workingHourService
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function getServiceProvidersWithPlannedWorkingHours(
        Carbon $startDate,
        Carbon $endDate,
        string $desiredResourceClass,
        User $currentUser = null
    ): array {
        $serviceProvidersWithPlannedWorkingHours = [];

        /** @var ServiceProvider $serviceProvider */
        foreach ($this->serviceProviderRepository->getWorkers() as $serviceProvider) {
            $desiredServiceProviderResource = $desiredResourceClass::make($serviceProvider);

            if ($desiredServiceProviderResource instanceof ServiceProviderShiftPlanResource) {
                $desiredServiceProviderResource->setStartDate($startDate)->setEndDate($endDate);
            }

            /*$plannedWorkingHours = $this->workingHourService->convertMinutesInHours(
                $this->workingHourService->calculateShiftTime($serviceProvider, $startDate, $endDate)
            );*/
            $weeklyWorkingHours = $this->workingHourService->calculateWeeklyWorkingHours(
                $serviceProvider,
                $startDate,
                $endDate
            );

            $serviceProvidersWithPlannedWorkingHours[] = [
                'service_provider' => $desiredServiceProviderResource->resolve(),
                //'plannedWorkingHours' => $plannedWorkingHours,
                'weeklyWorkingHours' => $weeklyWorkingHours,
                'dayServices' => $serviceProvider->dayServices?->groupBy('pivot.date'),
                'individual_times' => $serviceProvider->individualTimes()
                    ->individualByDateRange($startDate, $endDate)->get(),
                'shift_comments' => $serviceProvider->getShiftPlanCommentsForPeriod($startDate, $endDate),
            ];
        }

        if ($currentUser->getAttribute('shift_plan_user_sort_by_id')) {
            usort($serviceProvidersWithPlannedWorkingHours, function ($a, $b) use ($currentUser) {
                return match ($currentUser->getAttribute('shift_plan_user_sort_by_id')) {
                    'ALPHABETICALLY_ASCENDING_FIRST_NAME', 'ALPHABETICALLY_ASCENDING_LAST_NAME' =>
                    strcmp($a['service_provider']['provider_name'], $b['service_provider']['provider_name']),
                    'ALPHABETICALLY_DESCENDING_FIRST_NAME', 'ALPHABETICALLY_DESCENDING_LAST_NAME' =>
                    strcmp($b['service_provider']['provider_name'], $a['service_provider']['provider_name']),
                    default => 0,
                };
            });
        }

        return $serviceProvidersWithPlannedWorkingHours;
    }


    public function createShowDto(
        ServiceProvider $serviceProvider,
        UserService $userService,
        EventService $eventService,
        RoomService $roomService,
        EventTypeService $eventTypeService,
        ProjectService $projectService,
        ShiftQualificationService $shiftQualificationService
    ): ShowDto {
        [$startDate, $endDate] = $userService->getUserWorkerShiftPlanFilterStartAndEndDatesOrDefault(
            $userService->getAuthUser()
        );
        $requestedPeriod = iterator_to_array(
            CarbonPeriod::create($startDate, $endDate)->map(
                function (Carbon $date) {
                    return $date->format('d.m.Y');
                }
            )
        );
        $startOfWeek = $startDate->copy()->startOfWeek();
        $endOfWeek = $endDate->copy()->endOfWeek();

        $daysWithData = $eventService->getDaysWithEventsAndTotalPlannedWorkingHours(
            $serviceProvider->id,
            'service_provider',
            $startOfWeek,
            $endOfWeek
        );

        return ShowDto::newInstance()
            ->setServiceProvider(ServiceProviderShowResource::make($serviceProvider))
            ->setDateValue([$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->setWholeWeekDatePeriod(
                iterator_to_array(
                    CarbonPeriod::create($startOfWeek, $endOfWeek)
                        ->map(
                            function (Carbon $date) use ($requestedPeriod) {
                                return [
                                    'inRequestedTimeSpan' => in_array(
                                        $date->format('d.m.Y'),
                                        $requestedPeriod,
                                        true
                                    ),
                                    'full_day' => $date->format('d.m.Y'),
                                    'day' => $date->format('d.m.'),
                                    'day_string' => $date->shortDayName,
                                    'week_number' => $date->weekOfYear,
                                    'month_number' => $date->month,
                                    'is_monday' => $date->isMonday(),
                                    'is_weekend' => $date->isWeekend(),
                                    'day_without_format' => $date->format('Y-m-d'),
                                ];
                            }
                        )
                )
            )
            //->setEventsWithTotalPlannedWorkingHours($eventsWithTotalPlannedWorkingHours)
            //->setTotalPlannedWorkingHours((float) $totalPlannedWorkingHours)
            ->setRooms($roomService->getAllWithoutTrashed())
            ->setEventTypes($eventTypeService->getAll())
            ->setProjects($projectService->getAll())
            ->setShifts($this->getShiftsWithEventOrderedByStartAscending($serviceProvider))
            ->setShiftQualifications($shiftQualificationService->getAllOrderedByCreationDateAscending())
            ->setFirstProjectShiftTabId(
                $this->projectTabService->getFirstProjectTabWithTypeIdOrFirstProjectTabId(
                    ProjectTabComponentEnum::SHIFT_TAB
                )
            );
    }

    public function getShiftsWithEventOrderedByStartAscending(int|ServiceProvider $serviceProvider): Collection
    {
        return $this->serviceProviderRepository->getShiftsWithEventOrderedByStartAscending($serviceProvider);
    }

    public function searchServiceProviders(string $search): SupportCollection
    {
        return $this->serviceProviderRepository->scoutSearch($search);
    }
}
