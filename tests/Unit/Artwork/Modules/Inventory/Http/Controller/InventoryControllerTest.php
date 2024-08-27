<?php

namespace Tests\Unit\Artwork\Modules\Inventory\Http\Controller;

use Artwork\Modules\Calendar\Services\CalendarDataService;
use Artwork\Modules\Calendar\Services\CalendarService;
use Artwork\Modules\Craft\Services\CraftService;
use Artwork\Modules\Inventory\Http\Controllers\InventoryController;
use Artwork\Modules\InventoryManagement\Services\CraftInventoryItemService;
use Artwork\Modules\InventoryManagement\Services\CraftsInventoryColumnService;
use Artwork\Modules\InventoryManagement\Services\InventoryManagementUserFilterService;
use Artwork\Modules\InventoryScheduling\Services\CraftInventoryItemEventService;
use Illuminate\Auth\AuthManager;
use Illuminate\Database\Eloquent\Collection;
use Inertia\ResponseFactory;
use PHPUnit\Framework\TestCase;

class InventoryControllerTest extends TestCase
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public static function inventoryTestDataProvider(): array
    {
        $getAllOrderedCollection = Collection::make();
        $getAllWithInventoryCategoriesRelationsCollection = Collection::make();

        return [
            'test inventory' => [
                $getAllOrderedCollection,
                $getAllWithInventoryCategoriesRelationsCollection,
                1,
                [],
                'Inventory/InventoryManagement/Inventory',
                [
                    'columns' => $getAllOrderedCollection,
                    'crafts' => $getAllWithInventoryCategoriesRelationsCollection,
                    'craftFilters' => []
                ]
            ]
        ];
    }

    /** @dataProvider inventoryTestDataProvider */
    public function testInventory(
        Collection $getAllOrderedReturn,
        Collection $getAllWithInventoryCategoriesRelationsReturn,
        int $idReturn,
        array $getFilterOfUserReturn,
        string $renderComponent,
        array $renderArgs
    ): void {
        $authManagerMock = $this->getMockBuilder(AuthManager::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['guard', '__call'])
            ->getMock();

        $craftServiceMock = $this->getMockBuilder(CraftService::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getAllWithInventoryCategoriesRelations'])
            ->getMock();

        $craftsInventoryColumnServiceMock = $this->getMockBuilder(CraftsInventoryColumnService::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getAllOrdered'])
            ->getMock();

        $inventoryManagementUserFilterServiceMock = $this->getMockBuilder(InventoryManagementUserFilterService::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getFilterOfUser'])
            ->getMock();

        $calendarDataServiceMock = $this->getMockBuilder(CalendarDataService::class)
            ->disableOriginalConstructor()
            ->getMock();

        $craftInventoryItemServiceMock = $this->getMockBuilder(CraftInventoryItemService::class)
            ->disableOriginalConstructor()
            ->getMock();

        $craftInventoryItemEventServicesMock = $this->getMockBuilder(CraftInventoryItemEventService::class)
            ->disableOriginalConstructor()
            ->getMock();

        $responseFactoryMock = $this->getMockBuilder(ResponseFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['render'])
            ->getMock();

        $craftsInventoryColumnServiceMock->expects(self::once())
            ->method('getAllOrdered')
            ->willReturn($getAllOrderedReturn);

        $craftServiceMock->expects(self::once())
            ->method('getAllWithInventoryCategoriesRelations')
            ->willReturn($getAllWithInventoryCategoriesRelationsReturn);

        $authManagerMock->expects(self::once())
            ->method('__call')
            ->with('id')
            ->willReturn($idReturn);

        $inventoryManagementUserFilterServiceMock->expects(self::once())
            ->method('getFilterOfUser')
            ->with($idReturn)
            ->willReturn($getFilterOfUserReturn);

        $responseFactoryMock->expects(self::once())
            ->method('render')
            ->with($renderComponent, $renderArgs);

        $controller = new InventoryController(
            $authManagerMock,
            $craftServiceMock,
            $craftsInventoryColumnServiceMock,
            $inventoryManagementUserFilterServiceMock,
            $calendarDataServiceMock,
            $craftInventoryItemEventServicesMock,
            $responseFactoryMock
        );

        $controller->inventory();
    }
}
