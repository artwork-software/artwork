<?php

namespace Artwork\Modules\Freelancer\Services;

use Artwork\Modules\Calendar\Services\CalendarService;
use Artwork\Modules\Event\Services\EventService;
use Artwork\Modules\EventType\Http\Resources\EventTypeResource;
use Artwork\Modules\EventType\Services\EventTypeService;
use Artwork\Modules\Freelancer\DTOs\ShowDto;
use Artwork\Modules\Freelancer\Http\Resources\FreelancerShiftPlanResource;
use Artwork\Modules\Freelancer\Http\Resources\FreelancerShowResource;
use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\Freelancer\Repositories\FreelancerRepository;
use Artwork\Modules\Project\Services\ProjectService;
use Artwork\Modules\Project\Enum\ProjectTabComponentEnum;
use Artwork\Modules\Project\Services\ProjectTabService;
use Artwork\Modules\Room\Services\RoomService;
use Artwork\Modules\Shift\Services\ShiftQualificationService;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Services\UserService;
use Artwork\Modules\User\Services\WorkingHourService;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

readonly class FreelancerService
{
    public function __construct(
        private FreelancerRepository $freelancerRepository,
        private ProjectTabService $projectTabService,
        private WorkingHourService $workingHourService
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    //@todo: fix phpcs error - refactor function because complexity exceeds allowed maximum
    //phpcs:ignore Generic.Metrics.CyclomaticComplexity.MaxExceeded
    //phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh
    public function getFreelancersWithPlannedWorkingHours(
        Carbon $startDate,
        Carbon $endDate,
        string $desiredResourceClass,
        bool $addVacationsAndAvailabilities = false,
        User $currentUser = null
    ): array {
        $freelancersWithPlannedWorkingHours = [];

        /** @var Freelancer $freelancer */
        foreach ($this->freelancerRepository->getWorkers() as $freelancer) {
            $desiredFreelancerResource = $desiredResourceClass::make($freelancer);

            if ($desiredFreelancerResource instanceof FreelancerShiftPlanResource) {
                $desiredFreelancerResource->setStartDate($startDate)->setEndDate($endDate);
            }

            /*$plannedWorkingHours = $this->workingHourService->convertMinutesInHours(
                $this->workingHourService->calculateShiftTime($freelancer, $startDate, $endDate)
            );*/
            $weeklyWorkingHours = $this->workingHourService->calculateWeeklyWorkingHours(
                $freelancer,
                $startDate,
                $endDate
            );

            $freelancerData = [
                'freelancer' => $desiredFreelancerResource->resolve(),
                //'plannedWorkingHours' => $plannedWorkingHours, //$freelancer->plannedWorkingHours($startDate, $endDate),
                'weeklyWorkingHours' => $weeklyWorkingHours,
                'dayServices' => $freelancer->dayServices?->groupBy('pivot.date'),
                'individual_times' => $freelancer->individualTimes()
                    ->individualByDateRange($startDate, $endDate)->get(),
                'shift_comments' => $freelancer->getShiftPlanCommentsForPeriod($startDate, $endDate),
            ];

            if ($addVacationsAndAvailabilities) {
                $freelancerData['vacations'] = $freelancer->getVacationDays();
                $freelancerData['availabilities'] = $this->freelancerRepository
                    ->getAvailabilitiesBetweenDatesGroupedByFormattedDate(
                        $freelancer,
                        $startDate,
                        $endDate
                    );
            }

            $freelancersWithPlannedWorkingHours[] = $freelancerData;
        }

        if ($currentUser->getAttribute('shift_plan_user_sort_by_id')) {
            usort($freelancersWithPlannedWorkingHours, function ($a, $b) use ($currentUser) {
                return match ($currentUser->getAttribute('shift_plan_user_sort_by_id')) {
                    'ALPHABETICALLY_ASCENDING_FIRST_NAME' =>
                    strcmp($a['freelancer']['first_name'], $b['freelancer']['first_name']),
                    'ALPHABETICALLY_DESCENDING_FIRST_NAME' =>
                    strcmp($b['freelancer']['first_name'], $a['freelancer']['first_name']),
                    'ALPHABETICALLY_ASCENDING_LAST_NAME' =>
                    strcmp($a['freelancer']['last_name'], $b['freelancer']['last_name']),
                    'ALPHABETICALLY_DESCENDING_LAST_NAME' =>
                    strcmp($b['freelancer']['last_name'], $a['freelancer']['last_name']),
                    default => 0,
                };
            });
        }

        return $freelancersWithPlannedWorkingHours;
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function createShowDto(
        Freelancer $freelancer,
        UserService $userService,
        EventService $eventService,
        CalendarService $calendarService,
        RoomService $roomService,
        EventTypeService $eventTypeService,
        ProjectService $projectService,
        ShiftQualificationService $shiftQualificationService,
        Carbon $selectedDate,
        Carbon $selectedPeriodDate,
        ?string $month,
        ?string $vacationMonth
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
            $freelancer->id,
            'freelancer',
            $startOfWeek,
            $endOfWeek
        );

        [
            $calendarData,
            $dateToShow
        ] = $calendarService->getAvailabilityData(
            $freelancer,
            $month
        );

        return ShowDto::newInstance()
            ->setFreelancer(FreelancerShowResource::make($freelancer)->resolve())
            ->setFreelancerToEditWholeWeekDatePeriodVacations(
                $freelancer->getAttribute('vacations')
                    ->whereBetween(
                        'date',
                        [
                            $startOfWeek->format('Y-m-d'),
                            $endOfWeek->format('Y-m-d')
                        ]
                    )
            )
            ->setCalendarData($calendarData)
            ->setDateToShow($dateToShow)
            ->setVacationSelectCalendar($calendarService->createVacationAndAvailabilityPeriodCalendar($vacationMonth))
            ->setCreateShowDate(
                [
                    $selectedPeriodDate->isoFormat('MMMM YYYY'),
                    $selectedPeriodDate->copy()->startOfMonth()->toDate()
                ]
            )
            ->setShowVacationsAndAvailabilitiesDate($selectedDate->format('Y-m-d'))
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
                                    'day_without_format' => $date->format('Y-m-d'),
                                ];
                            }
                        )
                )
            )
            //->setEventsWithTotalPlannedWorkingHours($eventsWithTotalPlannedWorkingHours)
            //->setTotalPlannedWorkingHours((float) $totalPlannedWorkingHours)
            ->setRooms($roomService->getAllWithoutTrashed())
            ->setEventTypes(EventTypeResource::collection($eventTypeService->getAll())->resolve())
            ->setProjects($projectService->getAll())
            ->setVacations($this->getVacationsByDateOrderedByDateAscending($freelancer, $selectedDate))
            ->setShifts($this->getShiftsWithEventsOrderedByStart($freelancer))
            ->setAvailabilities($this->getAvailabilitiesByDateOrderedByDateAscending($freelancer, $selectedDate))
            ->setShiftQualifications($shiftQualificationService->getAllOrderedByCreationDateAscending())
            ->setFirstProjectShiftTabId(
                $this->projectTabService->getFirstProjectTabWithTypeIdOrFirstProjectTabId(
                    ProjectTabComponentEnum::SHIFT_TAB
                )
            );
    }

    public function getVacationsByDateOrderedByDateAscending(int|Freelancer $freelancer, Carbon $date): Collection
    {
        return $this->freelancerRepository->getVacationsByDateOrderedByDateAscending($freelancer, $date);
    }

    public function getAvailabilitiesByDateOrderedByDateAscending(int|Freelancer $freelancer, Carbon $date): Collection
    {
        return $this->freelancerRepository->getAvailabilitiesByDateOrderedByDateAscending($freelancer, $date);
    }

    public function getShiftsWithEventsOrderedByStart(int|Freelancer $freelancer): Collection
    {
        return $this->freelancerRepository->getShiftsWithEventsOrderedByStart($freelancer);
    }

    public function searchFreelancers(string $search): SupportCollection
    {
        return $this->freelancerRepository->scoutSearch($search);
    }
}
