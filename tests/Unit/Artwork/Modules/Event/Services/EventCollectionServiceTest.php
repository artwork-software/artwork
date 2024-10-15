<?php

namespace Tests\Unit\Artwork\Modules\Event\Services;

use Artwork\Modules\Event\Services\EventCollectionService;
use Artwork\Modules\Event\Repositories\EventRepository;
use Artwork\Modules\Room\Repositories\RoomRepository;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\Event\Models\Event;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection as SupportCollection;
use PHPUnit\Framework\TestCase;

class EventCollectionServiceTest extends TestCase
{
    private $roomRepository;
    private $eventRepository;
    private EventCollectionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->roomRepository = $this->createMock(RoomRepository::class);
        $this->eventRepository = $this->createMock(EventRepository::class);
        $this->service = new EventCollectionService($this->roomRepository, $this->eventRepository);
    }

    public function testCollectEventsForRoomsReturnsCorrectEvents()
    {
        $roomsWithEvents = collect([new Room(['id' => 1]), new Room(['id' => 2])]);
        $calendarPeriod = CarbonPeriod::create('2023-01-01', '2023-01-31');
        $calendarFilter = null;
        $project = null;
        $desiresInventorySchedulingResource = false;

        $this->roomRepository->method('findOrFail')->willReturn(new Room(['id' => 1]));
        $this->eventRepository->method('getEventsWithoutRoom')->willReturn(new SupportCollection());

        $result = $this->service->collectEventsForRooms(
            $roomsWithEvents,
            $calendarPeriod,
            $calendarFilter,
            $project,
            $desiresInventorySchedulingResource
        );

        $this->assertInstanceOf(SupportCollection::class, $result);
        $this->assertCount(2, $result);
    }

    public function testCollectEventsForRoomHandlesEmptyEvents()
    {
        $room = new Room(['id' => 1]);
        $calendarPeriod = CarbonPeriod::create('2023-01-01', '2023-01-31');
        $calendarFilter = null;
        $project = null;
        $desiresInventorySchedulingResource = false;

        $this->roomRepository->method('findOrFail')->willReturn($room);
        $this->eventRepository->method('getEventsWithoutRoom')->willReturn(new SupportCollection());

        $result = $this->service->collectEventsForRoom(
            $room,
            $calendarPeriod,
            $calendarFilter,
            $project,
            $desiresInventorySchedulingResource
        );

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testGetEventsWithoutRoomReturnsEvents()
    {
        $project = null;
        $with = null;

        $this->eventRepository->method('getEventsWithoutRoom')->willReturn(new SupportCollection([new Event()]));

        $result = $this->service->getEventsWithoutRoom($project, $with);

        $this->assertInstanceOf(SupportCollection::class, $result);
        $this->assertNotEmpty($result);
    }

    public function testCollectEventsForRoomsOnSpecificDaysReturnsCorrectEvents(): void
    {
        $desiredRooms = [1, 2];
        $desiredDays = ['2023-01-01', '2023-01-02'];
        $calendarFilter = null;
        $project = null;

        $this->roomRepository->method('findOrFail')->willReturn(new Room(['id' => 1]));
        $this->eventRepository->method('getEventsWithoutRoom')->willReturn(new SupportCollection());

        $result = $this->service->collectEventsForRoomsOnSpecificDays(
            $desiredRooms,
            $desiredDays,
            $calendarFilter,
            $project
        );

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
    }
}
