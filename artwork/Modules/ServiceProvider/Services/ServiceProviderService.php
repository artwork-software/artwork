<?php

namespace Artwork\Modules\ServiceProvider\Services;

use Artwork\Modules\Event\Services\EventService;
use Artwork\Modules\EventType\Services\EventTypeService;
use Artwork\Modules\Project\Services\ProjectService;
use Artwork\Modules\ProjectTab\Enums\ProjectTabComponentEnum;
use Artwork\Modules\ProjectTab\Services\ProjectTabService;
use Artwork\Modules\Room\Services\RoomService;
use Artwork\Modules\ServiceProvider\DTOs\ShowDto;
use Artwork\Modules\ServiceProvider\Http\Resources\ServiceProviderShiftPlanResource;
use Artwork\Modules\ServiceProvider\Http\Resources\ServiceProviderShowResource;
use Artwork\Modules\ServiceProvider\Models\ServiceProvider;
use Artwork\Modules\ServiceProvider\Repositories\ServiceProviderRepository;
use Artwork\Modules\ShiftQualification\Services\ShiftQualificationService;
use Artwork\Modules\User\Services\UserService;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Collection;

readonly class ServiceProviderService
{
    public function __construct(
        private ServiceProviderRepository $serviceProviderRepository,
        private ProjectTabService $projectTabService
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function getServiceProvidersWithPlannedWorkingHours(
        Carbon $startDate,
        Carbon $endDate,
        string $desiredResourceClass
    ): array {
        $serviceProvidersWithPlannedWorkingHours = [];

        /** @var ServiceProvider $serviceProvider */
        foreach ($this->serviceProviderRepository->getWorkers() as $serviceProvider) {
            $desiredServiceProviderResource = $desiredResourceClass::make($serviceProvider);

            if ($desiredServiceProviderResource instanceof ServiceProviderShiftPlanResource) {
                $desiredServiceProviderResource->setStartDate($startDate)->setEndDate($endDate);
            }

            $serviceProvidersWithPlannedWorkingHours[] = [
                'service_provider' => $desiredServiceProviderResource->resolve(),
                'plannedWorkingHours' => $serviceProvider->plannedWorkingHours($startDate, $endDate),
                'dayServices' => $serviceProvider->dayServices?->groupBy('pivot.date'),
            ];
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

        [
            $eventsWithTotalPlannedWorkingHours,
            $totalPlannedWorkingHours
        ] = $eventService->getDaysWithEventsWhereServiceProviderHasShiftsWithTotalPlannedWorkingHours(
            $serviceProvider->id,
            $startDate,
            $endDate
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
                                        $requestedPeriod
                                    ),
                                    'full_day' => $date->format('d.m.Y'),
                                    'day' => $date->format('d.m.'),
                                    'day_string' => $date->shortDayName,
                                    'week_number' => $date->weekOfYear,
                                    'month_number' => $date->month,
                                    'is_monday' => $date->isMonday(),
                                    'is_weekend' => $date->isWeekend(),
                                ];
                            }
                        )
                )
            )
            ->setEventsWithTotalPlannedWorkingHours($eventsWithTotalPlannedWorkingHours)
            ->setTotalPlannedWorkingHours((float) $totalPlannedWorkingHours)
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
}
