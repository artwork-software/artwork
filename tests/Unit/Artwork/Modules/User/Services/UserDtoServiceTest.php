<?php

namespace Tests\Unit\Artwork\Modules\User\Services;

use Artwork\Modules\Calendar\Services\CalendarService;
use Artwork\Modules\Event\Services\EventService;
use Artwork\Modules\EventType\Services\EventTypeService;
use Artwork\Modules\Project\Services\ProjectService;
use Artwork\Modules\Room\Services\RoomService;
use Artwork\Modules\ShiftQualification\Services\ShiftQualificationService;
use Artwork\Modules\User\DTOs\UserShiftPlanPageDto;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Services\UserDtoService;
use Artwork\Modules\User\Services\UserService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Tests\TestCase;

class UserDtoServiceTest extends TestCase
{
    public function testGetUserShiftPlanPageDto(): void
    {
        $roomService = $this->createMock(RoomService::class);
        $userService = $this->createMock(UserService::class);
        $calendarService = $this->createMock(CalendarService::class);
        $eventTypeService = $this->createMock(EventTypeService::class);
        $projectService = $this->createMock(ProjectService::class);
        $shiftQualificationService = $this->createMock(ShiftQualificationService::class);
        $eventService = $this->createMock(EventService::class);

        $userDtoService = new UserDtoService(
            $roomService,
            $userService,
            $calendarService,
            $eventTypeService,
            $projectService,
            $shiftQualificationService,
            $eventService
        );

        $user = $this->adminUser();
        $user->shift_calendar_filter = (object)[
            'start_date' => '2023-01-01',
            'end_date' => '2023-01-31'
        ];

        $selectedPeriodDate = Carbon::now();
        $selectedDate = Carbon::now();
        $month = '2023-01';
        $vacationMonth = '2023-02';

        $eventService->method('getDaysWithEventsWhereUserHasShiftsWithTotalPlannedWorkingHours')
            ->willReturn([[], 0]);

        $calendarService->method('getAvailabilityData')
            ->willReturn([[], []]);

        $calendarService->method('createVacationAndAvailabilityPeriodCalendar')
            ->willReturn(collect());

        $roomService->method('getAllWithoutTrashed')
            ->willReturn(new Collection());

        $projectService->method('getAll')
            ->willReturn(new Collection());

        $shiftQualificationService->method('getAllOrderedByCreationDateAscending')
            ->willReturn(new Collection());

        $userService->method('getUserShiftsOrderedByStartAscending')
            ->willReturn(new Collection());

        $userService->method('getUserVacationsByDateOrderedByDateAsc')
            ->willReturn(new Collection());

        $userService->method('getUserAvailabilitiesByDateOrderedByDateAsc')
            ->willReturn(new Collection());

        $eventTypeService->method('getAll')
            ->willReturn(new Collection());

        $result = $userDtoService->getUserShiftPlanPageDto($user, $selectedPeriodDate, $selectedDate, $month, $vacationMonth);

        $this->assertInstanceOf(UserShiftPlanPageDto::class, $result);
        $this->assertEquals('shiftplan', $result->getCurrentTab());
    }
}
