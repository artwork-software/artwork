<?php

namespace Tests\Unit\Artwork\Modules\InventoryManagement\Services;

use Artwork\Modules\InventoryManagement\Models\CraftInventoryItem;
use Artwork\Modules\InventoryManagement\Models\CraftInventoryItemCell;
use Artwork\Modules\InventoryManagement\Models\CraftsInventoryColumn;
use Artwork\Modules\InventoryManagement\Repositories\CraftInventoryItemRepository;
use Artwork\Modules\InventoryManagement\Repositories\CraftsInventoryColumnRepository;
use Artwork\Modules\InventoryManagement\Services\CraftInventoryItemCellService;
use Artwork\Modules\InventoryManagement\Services\CraftInventoryItemService;
use Artwork\Modules\InventoryManagement\Services\InventoryResourceCalculateModelsOrderService;
use AssertionError;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use PHPUnit\Framework\TestCase;
use Throwable;

class CraftInventoryItemServiceTest extends TestCase
{

    private readonly CraftsInventoryColumnRepository $craftsInventoryColumnRepositoryMock;

    private readonly CraftInventoryItemRepository $craftInventoryItemRepositoryMock;

    private readonly CraftInventoryItemCellService $craftInventoryItemCellServiceMock;

    private readonly InventoryResourceCalculateModelsOrderService $inventoryResourceCalculateModelsOrderServiceMock;

    private readonly CraftInventoryItem $craftInventoryItemMock;

    protected function setUp(): void
    {
        $this->craftsInventoryColumnRepositoryMock = $this
            ->getMockBuilder(CraftsInventoryColumnRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getAllOrdered'])
            ->getMock();

        $this->craftInventoryItemRepositoryMock = $this
            ->getMockBuilder(CraftInventoryItemRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'getAll',
                'getAllOfGroupOrderedByOrder',
                'getNewModelInstance',
                'saveOrFail',
                'updateOrFail',
                'find',
                'forceDelete',
            ])
            ->getMock();

        $this->craftInventoryItemCellServiceMock = $this
            ->getMockBuilder(CraftInventoryItemCellService::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->inventoryResourceCalculateModelsOrderServiceMock = $this
            ->getMockBuilder(InventoryResourceCalculateModelsOrderService::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getReorderedModels'])
            ->getMock();

        $this->craftInventoryItemMock = $this->getMockBuilder(CraftInventoryItem::class)
            ->onlyMethods(['getAttribute'])
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function getService(): CraftInventoryItemService
    {
        return new CraftInventoryItemService(
            $this->craftsInventoryColumnRepositoryMock,
            $this->craftInventoryItemRepositoryMock,
            $this->craftInventoryItemCellServiceMock,
            $this->inventoryResourceCalculateModelsOrderServiceMock
        );
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function createTestDataProvider(): array
    {
        $groupId = 1;
        $order = 0;
        return [
            'test create' => [
                $groupId,
                $order,
                [
                    'craft_inventory_group_id' => $groupId,
                    'order' => $order
                ]
            ]
        ];
    }

    /**
     * @dataProvider createTestDataProvider
     * @throws Throwable
     */
    public function testCreate(
        int $expectedGroupid,
        int $expectedOrder,
        array $expectedNewModelInstanceArgs
    ): void {
        $craftInventoryColumnMock = $this->getMockBuilder(CraftsInventoryColumn::class)
            ->onlyMethods(['getAttribute'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->craftInventoryItemRepositoryMock->expects(self::once())
            ->method('getNewModelInstance')
            ->with($expectedNewModelInstanceArgs)
            ->willReturn($this->craftInventoryItemMock);

        $this->craftInventoryItemRepositoryMock->expects(self::once())
            ->method('saveOrFail')
            ->with($this->craftInventoryItemMock)
            ->willReturn($this->craftInventoryItemMock);

        $this->craftsInventoryColumnRepositoryMock->expects(self::once())
            ->method('getAllOrdered')
            ->willReturn(
                Collection::make([
                    $craftInventoryColumnMock,
                    $craftInventoryColumnMock,
                    $craftInventoryColumnMock
                ])
            );

        $craftInventoryColumnMock->expects(self::exactly(3))
            ->method('getAttribute')
            ->with('id')
            ->willReturn(1);

        $this->craftInventoryItemMock->expects(self::exactly(3))
            ->method('getAttribute')
            ->with('id')
            ->willReturn(2);

        $this->craftInventoryItemCellServiceMock->expects(self::exactly(3))
            ->method('create')
            ->with(1, 2)
            ->willReturn($this->createStub(CraftInventoryItemCell::class));

        self::assertInstanceOf(
            CraftInventoryItem::class,
            $this->getService()->create($expectedGroupid, $expectedOrder)
        );
    }

    /**
     * @dataProvider createTestDataProvider
     * @throws Throwable
     */
    public function testCreateException(
        int $expectedGroupid,
        int $expectedOrder,
        array $expectedNewModelInstanceArgs
    ): void {
        $expectedException = new Exception('Test');

        $this->craftInventoryItemRepositoryMock->expects(self::once())
            ->method('getNewModelInstance')
            ->with($expectedNewModelInstanceArgs)
            ->willReturn($this->craftInventoryItemMock);

        $this->craftInventoryItemRepositoryMock->expects(self::once())
            ->method('saveOrFail')
            ->with($this->craftInventoryItemMock)
            ->willThrowException($expectedException);

        try {
            $this->getService()->create($expectedGroupid, $expectedOrder);
        } catch (Throwable $t) {
            self::assertSame(
                $expectedException,
                $t
            );
        }
    }

    /**
     * @throws Throwable
     */
    public function testCreateCellsInItemsForColumn(): void
    {
        $this->craftInventoryItemRepositoryMock->expects(self::once())
            ->method('getAll')
            ->willReturn(
                Collection::make([
                    $this->craftInventoryItemMock,
                    $this->craftInventoryItemMock,
                    $this->craftInventoryItemMock
                ])
            );

        $this->craftInventoryItemMock->expects(self::exactly(3))
            ->method('getAttribute')
            ->with('id')
            ->willReturn(2);

        $this->craftInventoryItemCellServiceMock->expects(self::exactly(3))
            ->method('create')
            ->with(1, 2, 'Test')
            ->willReturn($this->createStub(CraftInventoryItemCell::class));

        $craftsInventoryColumnMock = $this->getMockBuilder(CraftsInventoryColumn::class)
            ->onlyMethods(['getAttribute'])
            ->disableOriginalConstructor()
            ->getMock();

        $craftsInventoryColumnMock->expects(self::exactly(3))
            ->method('getAttribute')
            ->with('id')
            ->willReturn(1);

        $this->getService()->createCellsInItemsForColumn(
            $craftsInventoryColumnMock,
            'Test'
        );
    }

    /**
     * @throws Throwable
     */
    public function testCreateCellsInItemsForColumnException(): void
    {
        $expectedException = new Exception('Test');
        $this->craftInventoryItemRepositoryMock->expects(self::once())
            ->method('getAll')
            ->willReturn(
                Collection::make([
                    $this->craftInventoryItemMock,
                    $this->craftInventoryItemMock,
                    $this->craftInventoryItemMock
                ])
            );

        $this->craftInventoryItemMock->expects(self::once())
            ->method('getAttribute')
            ->with('id')
            ->willReturn(2);

        $this->craftInventoryItemCellServiceMock->expects(self::once())
            ->method('create')
            ->with(1, 2, 'Test')
            ->willThrowException($expectedException);

        $craftsInventoryColumnMock = $this->getMockBuilder(CraftsInventoryColumn::class)
            ->onlyMethods(['getAttribute'])
            ->disableOriginalConstructor()
            ->getMock();

        $craftsInventoryColumnMock->expects(self::once())
            ->method('getAttribute')
            ->with('id')
            ->willReturn(1);

        try {
            $this->getService()->createCellsInItemsForColumn(
                $craftsInventoryColumnMock,
                'Test'
            );
        } catch (Throwable $t) {
            self::assertSame(
                $expectedException,
                $t
            );
        }
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function updateOrderTestDataProvider(): array
    {
        return [
            'test updateOrder' => [
                1,
                0
            ]
        ];
    }

    /**
     * @dataProvider updateOrderTestDataProvider
     * @throws Throwable
     */
    public function testUpdateOrder(
        int $expectedGroupId,
        int $expectedOrder
    ): void {
        $expectedItemModels = Collection::make([
            $this->craftInventoryItemMock,
            $this->craftInventoryItemMock,
            $this->craftInventoryItemMock
        ]);

        $this->craftInventoryItemMock->expects(self::once())
            ->method('getAttribute')
            ->with('craft_inventory_group_id')
            ->willReturn($expectedGroupId);

        $this->craftInventoryItemRepositoryMock->expects(self::once())
            ->method('getAllOfGroupOrderedByOrder')
            ->with($expectedGroupId)
            ->willReturn($expectedItemModels);

        $this->inventoryResourceCalculateModelsOrderServiceMock->expects(self::once())
            ->method('getReorderedModels')
            ->with(
                $expectedItemModels,
                $expectedOrder,
                $this->craftInventoryItemMock
            )->willReturn($expectedItemModels->all());

        $this->craftInventoryItemRepositoryMock->expects($matcher = self::exactly(3))
            ->method('updateOrFail')
            ->willReturnCallback(
                function ($shouldBeCraftInventoryItem, $shouldBeAttributes) use ($matcher): CraftInventoryItem {
                    switch ($matcher->numberOfInvocations()) {
                        case 1:
                        case 2:
                        case 3:
                            self::assertInstanceOf(CraftInventoryItem::class, $shouldBeCraftInventoryItem);
                            self::assertIsArray($shouldBeAttributes);
                            self::assertArrayHasKey('order', $shouldBeAttributes);
                            self::assertSame($shouldBeAttributes['order'], ($matcher->numberOfInvocations() - 1));

                            return $shouldBeCraftInventoryItem;
                        default:
                            throw new AssertionError('Number of invocations not expected.');
                    }
                }
            );

        $this->getService()->updateOrder(
            $this->craftInventoryItemMock,
            $expectedOrder
        );
    }

    /**
     * @dataProvider updateOrderTestDataProvider
     * @throws Throwable
     */
    public function testUpdateOrderException(
        int $expectedGroupId,
        int $expectedOrder
    ): void {
        $expectedException = new Exception('Test');
        $expectedItemModels = Collection::make([
            $this->craftInventoryItemMock,
            $this->craftInventoryItemMock,
            $this->craftInventoryItemMock
        ]);

        $this->craftInventoryItemMock->expects(self::once())
            ->method('getAttribute')
            ->with('craft_inventory_group_id')
            ->willReturn($expectedGroupId);

        $this->craftInventoryItemRepositoryMock->expects(self::once())
            ->method('getAllOfGroupOrderedByOrder')
            ->with($expectedGroupId)
            ->willReturn($expectedItemModels);

        $this->inventoryResourceCalculateModelsOrderServiceMock->expects(self::once())
            ->method('getReorderedModels')
            ->with(
                $expectedItemModels,
                $expectedOrder,
                $this->craftInventoryItemMock
            )->willReturn($expectedItemModels->all());

        $this->craftInventoryItemRepositoryMock->expects(self::once())
            ->method('updateOrFail')
            ->willThrowException($expectedException);

        try {
            $this->getService()->updateOrder(
                $this->craftInventoryItemMock,
                $expectedOrder
            );
        } catch (Throwable $t) {
            self::assertSame($expectedException, $t);
        }
    }

    public function testForceDeleteWithCategoryModel(): void
    {
        $this->craftInventoryItemRepositoryMock->expects(self::exactly(2))
            ->method('forceDelete')
            ->with($this->craftInventoryItemMock)
            ->willReturnOnConsecutiveCalls(true, false);

        self::assertTrue($this->getService()->forceDelete($this->craftInventoryItemMock));
        self::assertFalse($this->getService()->forceDelete($this->craftInventoryItemMock));
    }

    public function testForceDeleteWithInt(): void
    {
        $this->craftInventoryItemRepositoryMock->expects(self::exactly(4))
            ->method('forceDelete')
            ->with($this->craftInventoryItemMock)
            ->willReturnOnConsecutiveCalls(true, false, true, false);

        $firstId = 1;
        $secondId = 2;
        $this->craftInventoryItemRepositoryMock->expects($matcher = self::exactly(4))
            ->method('find')
            ->willReturnCallback(
                function (int $id) use ($matcher, $firstId, $secondId): CraftInventoryItem {
                    switch ($matcher->numberOfInvocations()) {
                        case 1:
                        case 2:
                            self::assertSame($firstId, $id);
                            return $this->craftInventoryItemMock;
                        case 3:
                        case 4:
                            self::assertSame($secondId, $id);
                            return $this->craftInventoryItemMock;
                        default:
                            throw new AssertionError('Number of invocations not expected.');
                    }
                }
            );

        self::assertTrue($this->getService()->forceDelete($firstId));
        self::assertFalse($this->getService()->forceDelete($firstId));
        self::assertTrue($this->getService()->forceDelete($secondId));
        self::assertFalse($this->getService()->forceDelete($secondId));
    }
}
