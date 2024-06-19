<?php

namespace Artwork\Modules\User\Services;

use Artwork\Modules\Calendar\Filter\CalendarFilter;
use Artwork\Modules\Calendar\Services\CalendarService;
use Artwork\Modules\Event\Services\EventService;
use Artwork\Modules\EventType\Http\Resources\EventTypeResource;
use Artwork\Modules\EventType\Services\EventTypeService;
use Artwork\Modules\Project\Services\ProjectService;
use Artwork\Modules\Room\Services\RoomService;
use Artwork\Modules\ShiftQualification\Services\ShiftQualificationService;
use Artwork\Modules\User\DTOs\UserShiftPlanPageDto;
use Artwork\Modules\User\Http\Resources\UserShiftPlanResource;
use Artwork\Modules\User\Http\Resources\UserShowResource;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Repositories\UserRepository;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

readonly class UserService
{
    /**
     * @param UserRepository $userRepository
     */
    public function __construct(private UserRepository $userRepository)
    {
    }

    public function searchUsers(string $search): Collection
    {
        return $this->userRepository->searchUsers($search);
    }

    public function getAuthUser(bool $needCalendarAbo = false): ?User
    {
        /** @var User $user */
        $user = Auth::user();
        if($needCalendarAbo && !$user->calendarAbo){
            $user->load(['calendarAbo']);
        }

        return $user;
    }

    /**
     * @return Collection
     */
    public function getAllUsers(): Collection
    {
        return $this->userRepository->getAllUsers();
    }

    /**
     * @param int $id
     * @return User
     */
    public function findUser(int $id): User
    {
        return $this->userRepository->findUser($id);
    }

    /**
     * @return array<string, mixed>
     */
    public function getUsersWithPlannedWorkingHours(
        Carbon $startDate,
        Carbon $endDate,
        string $desiredResourceClass,
        bool $addVacationsAndAvailabilities = false
    ): array {
        $usersWithPlannedWorkingHours = [];

        /** @var User $user */
        foreach ($this->userRepository->getWorkers() as $user) {
            /** @var JsonResource $desiredResourceClass */
            $desiredUserResource = $desiredResourceClass::make($user);

            if ($desiredUserResource instanceof UserShiftPlanResource) {
                $desiredUserResource->setStartDate($startDate)->setEndDate($endDate);
            }

            $userData = [
                'user' => $desiredUserResource,
                'plannedWorkingHours' => $user->plannedWorkingHours($startDate, $endDate),
                'expectedWorkingHours' => ($user->weekly_working_hours / 7) * ($startDate->diffInDays($endDate) + 1),
                // dayServices group by pivot_date
                'dayServices' => $user->dayServices?->groupBy('pivot.date'),
            ];

            $userData['weeklyWorkingHours'] = $this->calculateWeeklyWorkingHours($user, $startDate, $endDate);

            if ($addVacationsAndAvailabilities) {
                $userData['vacations'] = $user->getVacationDays();
                $userData['availabilities'] = $this->userRepository
                    ->getAvailabilitiesBetweenDatesGroupedByFormattedDate(
                        $user,
                        $startDate,
                        $endDate
                    );
            }

            $usersWithPlannedWorkingHours[] = $userData;
        }

        // calculate the working hours for each calendar week ($startDate - $endDate) and add it to the user data
        return $usersWithPlannedWorkingHours;
    }

    /**
     * Berechnet die Arbeitsstunden für jede Kalenderwoche innerhalb eines bestimmten Datumsbereichs.
     *
     * @param User $user
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return string []
     */
    private function calculateWeeklyWorkingHours(User $user, Carbon $startDate, Carbon $endDate): array
    {
        // first create a carbon period for the given date range
        $period = Carbon::parse($startDate)->toPeriod($endDate);

        $weeklyWorkingHours = [];

        // iterate over each week and calculate the working hours
        foreach ($period as $week) {
            $startDate = $week->copy()->startOfWeek();
            $endDate = $week->copy()->endOfWeek();
            $workingHours = $user->plannedWorkingHours(
                    $startDate,
                    $endDate
                ) - $user->weekly_working_hours;
            $weeklyWorkingHours[$week->format('W')] = $workingHours;
        }

        return $weeklyWorkingHours;
    }

    public function getAuthUserCrafts(): Collection
    {
        return $this->getAuthUser()->crafts;
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function getUserShiftPlanPageDto(
        User $user,
        CalendarService $calendarService,
        EventService $eventService,
        RoomService $roomService,
        EventTypeService $eventTypeService,
        ProjectService $projectService,
        ShiftQualificationService $shiftQualificationService,
        Carbon $selectedPeriodDate,
        Carbon $selectedDate,
        ?string $month,
        ?string $vacationMonth
    ): UserShiftPlanPageDto {
        $hasUserShiftCalendarFilterDates = !is_null($user->shift_calendar_filter?->start_date) &&
            !is_null($user->shift_calendar_filter?->end_date);
        $startDate = $hasUserShiftCalendarFilterDates ?
            Carbon::create($user->shift_calendar_filter->start_date)->startOfDay() :
            Carbon::now()->startOfDay();
        $endDate = $hasUserShiftCalendarFilterDates ?
            Carbon::create($user->shift_calendar_filter->end_date)->endOfDay() :
            Carbon::now()->addWeeks()->endOfDay();

        [
            $daysWithEvents,
            $totalPlannedWorkingHours
        ] = $eventService->getDaysWithEventsWhereUserHasShiftsWithTotalPlannedWorkingHours(
            $user->id,
            $startDate,
            $endDate
        );

        [
            $calendarData,
            $dateToShow
        ] = $calendarService->getAvailabilityData(
            $user,
            $month
        );

        return UserShiftPlanPageDto::newInstance()
            ->setUserToEdit(UserShowResource::make($user))
            ->setEventTypes(EventTypeResource::collection($eventTypeService->getAll())->resolve())
            ->setCurrentTab('shiftplan')
            ->setCalendarData($calendarData)
            ->setDateToShow($dateToShow)
            ->setCreateShowDate(
                [
                    $selectedPeriodDate->isoFormat('MMMM YYYY'),
                    $selectedPeriodDate->copy()->startOfMonth()->toDate()
                ]
            )
            ->setShowVacationsAndAvailabilitiesDate($selectedDate->format('Y-m-d'))
            ->setDateValue([$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->setDaysWithEvents($daysWithEvents)
            ->setTotalPlannedWorkingHours((float)$totalPlannedWorkingHours)
            ->setVacationSelectCalendar($calendarService->createVacationAndAvailabilityPeriodCalendar($vacationMonth))
            ->setRooms($roomService->getAllWithoutTrashed())
            ->setProjects($projectService->getAll())
            ->setShiftQualifications($shiftQualificationService->getAllOrderedByCreationDateAscending())
            ->setShifts($this->getUserShiftsOrderedByStartAscending($user))
            ->setVacations($this->getUserVacationsByDateOrderedByDateAsc($user, $selectedDate))
            ->setAvailabilities($this->getUserAvailabilitiesByDateOrderedByDateAsc($user, $selectedDate));
    }

    public function getUserVacationsByDateOrderedByDateAsc(int|User $user, Carbon $selectedDate): Collection
    {
        return $this->userRepository->getUserVacationsByDateOrderedByDateAsc($user, $selectedDate);
    }

    public function getUserAvailabilitiesByDateOrderedByDateAsc(int|User $user, Carbon $selectedDate): Collection
    {
        return $this->userRepository->getUserAvailabilitiesByDateOrderedByDateAsc(
            $user,
            $selectedDate
        );
    }

    public function getUserShiftsOrderedByStartAscending(int|User $user): Collection
    {
        return $this->userRepository->getShiftsOrderedByStartAscending($user);
    }

    /**
     * @return array<int, Carbon>
     */
    public function getUserCalendarFilterDatesOrDefault(?CalendarFilter $userCalendarFilter): array
    {
        if (!$userCalendarFilter) {
            $userCalendarFilter = new \stdClass();
        }
        $hasUserCalendarFilterDates = !is_null($userCalendarFilter?->start_date) &&
            !is_null($userCalendarFilter?->end_date);
        $startDate = $hasUserCalendarFilterDates ?
            Carbon::create($userCalendarFilter->start_date)->startOfDay() :
            Carbon::now()->startOfDay();
        $endDate = $hasUserCalendarFilterDates ?
            Carbon::create($userCalendarFilter->end_date)->endOfDay() :
            Carbon::now()->addWeeks()->endOfDay();

        return [$startDate, $endDate];
    }

    /**
     * @return array<int, Carbon>
     */
    public function getUserShiftCalendarFilterDatesOrDefault(User $user): array
    {
        $userShiftCalendarFilter = $user->shift_calendar_filter;

        $hasUserShiftCalendarFilterDates = !is_null($userShiftCalendarFilter?->start_date) &&
            !is_null($userShiftCalendarFilter?->end_date);
        $startDate = $hasUserShiftCalendarFilterDates ?
            Carbon::create($userShiftCalendarFilter->start_date)->startOfDay() :
            Carbon::now()->startOfDay();
        $endDate = $hasUserShiftCalendarFilterDates ?
            Carbon::create($userShiftCalendarFilter->end_date)->endOfDay() :
            Carbon::now()->addWeeks()->endOfDay();

        return [$startDate, $endDate];
    }

    public function getAdminUser(): User
    {
        return $this->userRepository->getAdminUser();
    }
}
