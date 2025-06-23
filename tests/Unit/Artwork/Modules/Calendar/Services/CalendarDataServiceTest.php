<?php

namespace Tests\Unit\Artwork\Modules\Calendar\Services;

use Artwork\Modules\Calendar\Services\CalendarDataService;
use Artwork\Modules\Event\Services\EventCollectionService;
use Artwork\Modules\Filter\Services\FilterService;
use Artwork\Modules\Project\Services\ProjectService;
use Artwork\Modules\Room\Repositories\RoomRepository;
use Artwork\Modules\User\Services\UserService;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class CalendarDataServiceTest extends TestCase
{
    private $calendarDataService;
    private $roomRepositoryMock;
    private $eventCollectionServiceMock;
    private $filterServiceMock;
    private $userServiceMock;
    private $projectServiceMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->roomRepositoryMock = $this->createMock(RoomRepository::class);
        $this->eventCollectionServiceMock = $this->createMock(EventCollectionService::class);
        $this->filterServiceMock = $this->createMock(FilterService::class);
        $this->userServiceMock = $this->createMock(UserService::class);
        $this->projectServiceMock = $this->createMock(ProjectService::class);

        $this->calendarDataService = new CalendarDataService(
            $this->roomRepositoryMock,
            $this->eventCollectionServiceMock,
            $this->filterServiceMock,
            $this->userServiceMock,
            $this->projectServiceMock
        );
    }

    public function testCreateCalendarData(): void
    {
        $startDate = Carbon::create(2023, 10, 1);
        $endDate = Carbon::create(2023, 10, 7);
        $calendarFilter = null;
        $project = null;
        $room = null;
        $desiresInventorySchedulingResource = null;

        $this->roomRepositoryMock->method('getFilteredRoomsBy')->willReturn([]);
        $this->eventCollectionServiceMock->method('collectEventsForRooms')->willReturn([]);
        $this->eventCollectionServiceMock->method('getEventsWithoutRoom')->willReturn([]);
        $this->filterServiceMock->method('getCalendarFilterDefinitions')->willReturn([]);
        $this->filterServiceMock->method('getPersonalFilter')->willReturn([]);

        $result = $this->calendarDataService->createCalendarData(
            $startDate,
            $endDate,
            $calendarFilter,
            $project,
            $room,
            $desiresInventorySchedulingResource
        );

        $this->assertIsArray($result);
        $this->assertArrayHasKey('days', $result);
        $this->assertArrayHasKey('dateValue', $result);
        $this->assertArrayHasKey('calendarType', $result);
        $this->assertArrayHasKey('selectedDate', $result);
        $this->assertArrayHasKey('roomsWithEvents', $result);
        $this->assertArrayHasKey('eventsWithoutRoom', $result);
        $this->assertArrayHasKey('filterOptions', $result);
        $this->assertArrayHasKey('personalFilters', $result);
        $this->assertArrayHasKey('user_filters', $result);
    }
}
