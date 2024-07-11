<?php

namespace Tests\Unit\Artwork\Modules\Inventory\Http\Controller;

use App\Http\Controllers\FilterController;
use Artwork\Modules\Area\Services\AreaService;
use Artwork\Modules\Calendar\Services\CalendarService;
use Artwork\Modules\Craft\Services\CraftService;
use Artwork\Modules\EventType\Services\EventTypeService;
use Artwork\Modules\Filter\Services\FilterService;
use Artwork\Modules\Inventory\Http\Controller\InventoryController;
use Artwork\Modules\InventoryManagement\Services\CraftsInventoryColumnService;
use Artwork\Modules\InventoryManagement\Services\InventoryManagementUserFilterService;
use Artwork\Modules\Project\Services\ProjectService;
use Artwork\Modules\Room\Services\RoomService;
use Artwork\Modules\RoomAttribute\Services\RoomAttributeService;
use Artwork\Modules\RoomCategory\Services\RoomCategoryService;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Services\UserService;
use Carbon\Carbon;
use Illuminate\Auth\AuthManager;
use Illuminate\Database\Eloquent\Collection;
use Inertia\ResponseFactory;
use PHPUnit\Framework\TestCase;
use Throwable;

class InventoryControllerTest extends TestCase
{

    private readonly AuthManager $authManagerMock;

    private readonly CraftService $craftServiceMock;

    private readonly CraftsInventoryColumnService $craftsInventoryColumnServiceMock;

    private readonly InventoryManagementUserFilterService $inventoryManagementUserFilterServiceMock;

    private readonly CalendarService $calendarServiceMock;

    private readonly ResponseFactory $responseFactoryMock;

    protected function setUp(): void
    {
        $this->authManagerMock = $this->getMockBuilder(AuthManager::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['guard', '__call'])
            ->getMock();

        $this->craftServiceMock = $this->getMockBuilder(CraftService::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getAllWithInventoryCategoriesRelations', 'getCraftsWithInventory'])
            ->getMock();

        $this->craftsInventoryColumnServiceMock = $this
            ->getMockBuilder(CraftsInventoryColumnService::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getAllOrdered'])
            ->getMock();

        $this->inventoryManagementUserFilterServiceMock = $this
            ->getMockBuilder(InventoryManagementUserFilterService::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getFilterOfUser'])
            ->getMock();

        $this->calendarServiceMock = $this->getMockBuilder(CalendarService::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->responseFactoryMock = $this->getMockBuilder(ResponseFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['render'])
            ->getMock();
    }

    private function getController(): InventoryController
    {
        return new InventoryController(
            $this->authManagerMock,
            $this->craftServiceMock,
            $this->craftsInventoryColumnServiceMock,
            $this->inventoryManagementUserFilterServiceMock,
            $this->calendarServiceMock,
            $this->responseFactoryMock
        );
    }

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
        $this->craftsInventoryColumnServiceMock->expects(self::once())
            ->method('getAllOrdered')
            ->willReturn($getAllOrderedReturn);

        $this->craftServiceMock->expects(self::once())
            ->method('getAllWithInventoryCategoriesRelations')
            ->willReturn($getAllWithInventoryCategoriesRelationsReturn);

        $this->authManagerMock->expects(self::once())
            ->method('__call')
            ->with('id')
            ->willReturn($idReturn);

        $this->inventoryManagementUserFilterServiceMock->expects(self::once())
            ->method('getFilterOfUser')
            ->with($idReturn)
            ->willReturn($getFilterOfUserReturn);

        $this->responseFactoryMock->expects(self::once())
            ->method('render')
            ->with($renderComponent, $renderArgs);

        $this->getController()->inventory();
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function schedulingTestDataProvider(): array
    {
        $craftCollection = Collection::make();
        return [
            'test scheduling' => [
                'calendar_filter',
                null,
                [Carbon::now(), Carbon::now()],
                $craftCollection,
                [
                    'dateValue' => '',
                    'roomsWithEvents' => '',
                    'days' => ''
                ],
                'Inventory/Scheduling',
                [
                    'dateValue' => '',
                    'calendar' => '',
                    'days' => '',
                    'crafts' => $craftCollection
                ]
            ]
        ];
    }

    /**
     * @dataProvider schedulingTestDataProvider
     * @throws Throwable
     */
    public function testScheduling(
        string $expectedUserGetAttributeKey,
        null $expectedUserGetAttributeReturnValue,
        array $expectedGetUserCalendarFilterDatesOrDefaultReturnValue,
        Collection $expectedGetCraftsWithInventoryReturnValue,
        array $expectedCreateCalendarDataReturnValue,
        string $renderComponent,
        array $renderArgs,
    ): void {
        $projectServiceStub = $this->createStub(ProjectService::class);
        $roomServiceStub = $this->createStub(RoomService::class);
        $filterServiceStub = $this->createStub(FilterService::class);
        $filterControllerStub = $this->createStub(FilterController::class);
        $roomCategoryServiceStub = $this->createStub(RoomCategoryService::class);
        $roomAttributeServiceStub = $this->createStub(RoomAttributeService::class);
        $eventTypeServiceStub = $this->createStub(EventTypeService::class);
        $areaServiceStub = $this->createStub(AreaService::class);

        $userMock = $this->getMockBuilder(User::class)
            ->onlyMethods(['getAttribute'])
            ->disableOriginalConstructor()
            ->getMock();

        $userServiceMock = $this->getMockBuilder(UserService::class)
            ->onlyMethods(['getUserCalendarFilterDatesOrDefault'])
            ->disableOriginalConstructor()
            ->getMock();

        $userMock->expects($this->once())
            ->method('getAttribute')
            ->with($expectedUserGetAttributeKey)
            ->willReturn($expectedUserGetAttributeReturnValue);

        $userServiceMock->expects($this->once())
            ->method('getUserCalendarFilterDatesOrDefault')
            ->willReturn($expectedGetUserCalendarFilterDatesOrDefaultReturnValue);

        $this->calendarServiceMock->expects($this->once())
            ->method('createCalendarData')
            ->with(
                $expectedGetUserCalendarFilterDatesOrDefaultReturnValue[0],
                $expectedGetUserCalendarFilterDatesOrDefaultReturnValue[1],
                $userServiceMock,
                $filterServiceStub,
                $filterControllerStub,
                $roomServiceStub,
                $roomCategoryServiceStub,
                $roomAttributeServiceStub,
                $eventTypeServiceStub,
                $areaServiceStub,
                $projectServiceStub
            )->willReturn($expectedCreateCalendarDataReturnValue);

        $this->craftServiceMock->expects($this->once())
            ->method('getCraftsWithInventory')
            ->willReturn($expectedGetCraftsWithInventoryReturnValue);

        $this->responseFactoryMock->expects(self::once())
            ->method('render')
            ->with($renderComponent, $renderArgs);

        $this->getController()->scheduling(
            $userMock,
            $projectServiceStub,
            $roomServiceStub,
            $userServiceMock,
            $filterServiceStub,
            $filterControllerStub,
            $roomCategoryServiceStub,
            $roomAttributeServiceStub,
            $eventTypeServiceStub,
            $areaServiceStub
        );
    }
}
