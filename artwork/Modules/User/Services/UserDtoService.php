<?php

namespace Artwork\Modules\User\Services;

use Artwork\Modules\Calendar\Services\CalendarService;
use Artwork\Modules\Event\Services\EventService;
use Artwork\Modules\EventType\Http\Resources\EventTypeResource;
use Artwork\Modules\EventType\Services\EventTypeService;
use Artwork\Modules\Project\Services\ProjectService;
use Artwork\Modules\Room\Services\RoomService;
use Artwork\Modules\ShiftQualification\Services\ShiftQualificationService;
use Artwork\Modules\User\DTOs\UserShiftPlanPageDto;
use Artwork\Modules\User\Http\Resources\UserShowResource;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Services\UserService;
use Carbon\Carbon;

readonly class UserDtoService
{
    public function __construct(
        private RoomService $roomService,
        private UserService $userService,
        private CalendarService $calendarService,
        private EventTypeService $eventTypeService,
        private ProjectService $projectService,
        private ShiftQualificationService $shiftQualificationService,
        private EventService $eventService
    ) {
    }

    public function getUserShiftPlanPageDto(
        User $user,
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
        ] = $this->eventService->getDaysWithEventsWhereUserHasShiftsWithTotalPlannedWorkingHours(
            $user->id,
            $startDate,
            $endDate
        );

        [
            $calendarData,
            $dateToShow
        ] = $this->calendarService->getAvailabilityData(
            $user,
            $month
        );

        return UserShiftPlanPageDto::newInstance()
            ->setUserToEdit(UserShowResource::make($user))
            ->setEventTypes(EventTypeResource::collection($this->eventTypeService->getAll())->resolve())
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
            ->setVacationSelectCalendar($this->calendarService->createVacationAndAvailabilityPeriodCalendar($vacationMonth))
            ->setRooms($this->roomService->getAllWithoutTrashed())
            ->setProjects($this->projectService->getAll())
            ->setShiftQualifications($this->shiftQualificationService->getAllOrderedByCreationDateAscending())
            ->setShifts($this->userService->getUserShiftsOrderedByStartAscending($user))
            ->setVacations($this->userService->getUserVacationsByDateOrderedByDateAsc($user, $selectedDate))
            ->setAvailabilities($this->userService->getUserAvailabilitiesByDateOrderedByDateAsc($user, $selectedDate));
    }
}
