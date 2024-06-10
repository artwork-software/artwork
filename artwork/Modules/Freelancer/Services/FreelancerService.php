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
use Artwork\Modules\Room\Services\RoomService;
use Artwork\Modules\ShiftQualification\Services\ShiftQualificationService;
use Artwork\Modules\User\Services\UserService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

readonly class FreelancerService
{
    public function __construct(private FreelancerRepository $freelancerRepository)
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function getFreelancersWithPlannedWorkingHours(
        Carbon $startDate,
        Carbon $endDate,
        string $desiredResourceClass,
        bool $addVacationsAndAvailabilities = false
    ): array {
        $freelancersWithPlannedWorkingHours = [];

        /** @var Freelancer $freelancer */
        foreach ($this->freelancerRepository->getWorkers() as $freelancer) {
            $desiredFreelancerResource = $desiredResourceClass::make($freelancer);

            if ($desiredFreelancerResource instanceof FreelancerShiftPlanResource) {
                $desiredFreelancerResource->setStartDate($startDate)->setEndDate($endDate);
            }

            $freelancerData = [
                'freelancer' => $desiredFreelancerResource,
                'plannedWorkingHours' => $freelancer->plannedWorkingHours($startDate, $endDate),
                'dayServices' => $freelancer->dayServices?->groupBy('pivot.date'),
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
        [$startDate, $endDate] = $userService->getUserShiftCalendarFilterDatesOrDefault($userService->getAuthUser());

        [
            $daysWithEvents,
            $totalPlannedWorkingHours
        ] = $eventService->getDaysWithEventsWhereFreelancerHasShiftsWithTotalPlannedWorkingHours(
            $freelancer->id,
            $startDate,
            $endDate
        );

        [
            $calendarData,
            $dateToShow
        ] = $calendarService->getAvailabilityData(
            $freelancer,
            $month
        );

        return ShowDto::newInstance()
            ->setFreelancer(FreelancerShowResource::make($freelancer))
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
            ->setDaysWithEvents($daysWithEvents)
            ->setTotalPlannedWorkingHours((float) $totalPlannedWorkingHours)
            ->setRooms($roomService->getAllWithoutTrashed())
            ->setEventTypes(EventTypeResource::collection($eventTypeService->getAll())->resolve())
            ->setProjects($projectService->getAll())
            ->setVacations($this->getVacationsByDateOrderedByDateAscending($freelancer, $selectedDate))
            ->setShifts($this->getShiftsWithEventsOrderedByStart($freelancer))
            ->setAvailabilities($this->getAvailabilitiesByDateOrderedByDateAscending($freelancer, $selectedDate))
            ->setShiftQualifications($shiftQualificationService->getAllOrderedByCreationDateAscending());
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
}
