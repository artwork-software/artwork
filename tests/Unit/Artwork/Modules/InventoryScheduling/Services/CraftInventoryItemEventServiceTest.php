<?php

namespace Tests\Unit\Artwork\Modules\InventoryScheduling\Services;

use Artwork\Modules\Event\Services\EventService;
use Artwork\Modules\InventoryScheduling\Repositories\CraftInventoryItemEventRepository;
use Artwork\Modules\InventoryScheduling\Services\CraftInventoryItemEventService;
use PHPUnit\Framework\TestCase;

class CraftInventoryItemEventServiceTest extends TestCase
{
    private readonly CraftInventoryItemEventRepository $craftInventoryItemEventRepositoryMock;

    private readonly EventService $eventServiceMock;

    protected function setUp(): void
    {
        $this->craftInventoryItemEventRepositoryMock = $this->getMockBuilder(CraftInventoryItemEventRepository::class)
            ->onlyMethods([])
            ->disableOriginalConstructor()
            ->getMock();

        $this->eventServiceMock = $this->getMockBuilder(EventService::class)
            ->onlyMethods([])
            ->disableOriginalConstructor()
            ->getMock();
    }

    private function getService(): CraftInventoryItemEventService
    {
        return new CraftInventoryItemEventService(
            $this->craftInventoryItemEventRepositoryMock,
            $this->eventServiceMock
        );
    }
}
