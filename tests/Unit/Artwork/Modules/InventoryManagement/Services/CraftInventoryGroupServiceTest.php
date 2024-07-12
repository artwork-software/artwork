<?php

namespace Tests\Unit\Artwork\Modules\InventoryManagement\Services;

use Artwork\Modules\InventoryManagement\Models\CraftInventoryGroup;
use Artwork\Modules\InventoryManagement\Repositories\CraftInventoryGroupRepository;
use Artwork\Modules\InventoryManagement\Services\CraftInventoryGroupService;
use Artwork\Modules\InventoryManagement\Services\InventoryResourceCalculateModelsOrderService;
use AssertionError;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use PHPUnit\Framework\TestCase;
use Throwable;

class CraftInventoryGroupServiceTest extends TestCase
{
    private readonly CraftInventoryGroupRepository $craftInventoryGroupRepositoryMock;

    private readonly InventoryResourceCalculateModelsOrderService $inventoryResourceCalculateModelsOrderServiceMock;

    private readonly CraftInventoryGroup $craftInventoryGroupMock;

    protected function setUp(): void
    {
        $this->craftInventoryGroupRepositoryMock = $this
            ->getMockBuilder(CraftInventoryGroupRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'getNewModelInstance',
                'saveOrFail',
                'updateOrFail',
                'find',
                'forceDelete',
                'getAllByCategoryIdOrderedByOrder',
                'getGroupCountForCategory'
            ])
            ->getMock();

        $this->inventoryResourceCalculateModelsOrderServiceMock = $this
            ->getMockBuilder(InventoryResourceCalculateModelsOrderService::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getReorderedModels'])
            ->getMock();

        $this->craftInventoryGroupMock = $this->getMockBuilder(CraftInventoryGroup::class)
            ->onlyMethods(['getAttribute'])
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function getService(): CraftInventoryGroupService
    {
        return new CraftInventoryGroupService(
            $this->craftInventoryGroupRepositoryMock,
            $this->inventoryResourceCalculateModelsOrderServiceMock
        );
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function createTestDataProvider(): array
    {
        $categoryId = 1;
        $order = 1;
        $name = 'Test';
        return [
            'test create' => [
                $categoryId,
                $order,
                $name,
                [
                    'craft_inventory_category_id' => $categoryId,
                    'name' => $name,
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
        int $expectedCategoryId,
        int $expectedOrder,
        string $expectedName,
        array $expectedNewModelInstanceArgs
    ): void {
        $this->craftInventoryGroupRepositoryMock->expects(self::once())
            ->method('getNewModelInstance')
            ->with($expectedNewModelInstanceArgs)
            ->willReturn($this->craftInventoryGroupMock);

        $this->craftInventoryGroupRepositoryMock->expects(self::once())
            ->method('getGroupCountForCategory')
            ->with($expectedCategoryId)
            ->willReturn($expectedOrder);

        $this->craftInventoryGroupRepositoryMock->expects(self::once())
            ->method('saveOrFail')
            ->willReturn($this->craftInventoryGroupMock);

        self::assertInstanceOf(
            CraftInventoryGroup::class,
            $this->getService()->create($expectedCategoryId, $expectedName)
        );
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function createExceptionTestDataProvider(): array
    {
        $categoryId = 1;
        $order = 1;
        $name = 'Test';
        return [
            'test create exception' => [
                $categoryId,
                $order,
                $name,
                [
                    'craft_inventory_category_id' => $categoryId,
                    'name' => $name,
                    'order' => $order
                ],
                new Exception('Test')
            ]
        ];
    }

    /**
     * @dataProvider createExceptionTestDataProvider
     */
    public function testCreateException(
        int $expectedCraftId,
        int $expectedOrder,
        string $expectedName,
        array $expectedNewModelInstanceArgs,
        Exception $expectedException
    ): void {
        $this->craftInventoryGroupRepositoryMock->expects(self::once())
            ->method('getNewModelInstance')
            ->with($expectedNewModelInstanceArgs)
            ->willReturn($this->craftInventoryGroupMock);

        $this->craftInventoryGroupRepositoryMock->expects(self::once())
            ->method('getGroupCountForCategory')
            ->with($expectedCraftId)
            ->willReturn($expectedOrder);

        $this->craftInventoryGroupRepositoryMock->expects(self::once())
            ->method('saveOrFail')
            ->willThrowException($expectedException);

        try {
            $this->getService()->create($expectedCraftId, $expectedName);
        } catch (Throwable $t) {
            self::assertSame($expectedException, $t);
        }
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function updateNameTestDataProvider(): array
    {
        $name = 'Test';
        return [
            'test updateName' => [
                $name,
                [
                    'name' => $name
                ]
            ]
        ];
    }

    /**
     * @dataProvider updateNameTestDataProvider
     * @throws Throwable
     */
    public function testUpdateName(
        string $expectedName,
        array $expectedUpdateOrFailArgs
    ): void {
        $this->craftInventoryGroupRepositoryMock->expects(self::once())
            ->method('updateOrFail')
            ->with($this->craftInventoryGroupMock, $expectedUpdateOrFailArgs)
            ->willReturn($this->craftInventoryGroupMock);

        $this->getService()->updateName($expectedName, $this->craftInventoryGroupMock);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function updateNameExceptionTestDataProvider(): array
    {
        $name = 'Test';
        return [
            'test updateName exception' => [
                $name,
                [
                    'name' => $name,
                ],
                new Exception('Test')
            ]
        ];
    }

    /**
     * @dataProvider updateNameExceptionTestDataProvider
     */
    public function testUpdateNameException(
        string $expectedName,
        array $expectedUpdateOrFailArgs,
        Exception $expectedException
    ): void {
        $this->craftInventoryGroupRepositoryMock->expects(self::once())
            ->method('updateOrFail')
            ->with($this->craftInventoryGroupMock, $expectedUpdateOrFailArgs)
            ->willThrowException($expectedException);

        try {
            $this->getService()->updateName($expectedName, $this->craftInventoryGroupMock);
        } catch (Throwable $t) {
            self::assertSame($expectedException, $t);
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
        int $expectedCategoryId,
        int $expectedOrder
    ): void {
        $expectedCategoryModels = Collection::make([
            $this->craftInventoryGroupMock,
            $this->craftInventoryGroupMock,
            $this->craftInventoryGroupMock
        ]);

        $this->craftInventoryGroupMock->expects(self::once())
            ->method('getAttribute')
            ->with('craft_inventory_category_id')
            ->willReturn($expectedCategoryId);

        $this->craftInventoryGroupRepositoryMock->expects(self::once())
            ->method('getAllByCategoryIdOrderedByOrder')
            ->with($expectedCategoryId)
            ->willReturn($expectedCategoryModels);

        $this->inventoryResourceCalculateModelsOrderServiceMock->expects(self::once())
            ->method('getReorderedModels')
            ->with(
                $expectedCategoryModels,
                $expectedOrder,
                $this->craftInventoryGroupMock
            )->willReturn($expectedCategoryModels->all());

        $this->craftInventoryGroupRepositoryMock->expects($matcher = self::exactly(3))
            ->method('updateOrFail')
            ->willReturnCallback(
                function ($shouldBeCraftInventoryGroup, $shouldBeAttributes) use ($matcher): CraftInventoryGroup {
                    switch ($matcher->numberOfInvocations()) {
                        case 1:
                        case 2:
                        case 3:
                            self::assertInstanceOf(CraftInventoryGroup::class, $shouldBeCraftInventoryGroup);
                            self::assertIsArray($shouldBeAttributes);
                            self::assertArrayHasKey('order', $shouldBeAttributes);
                            self::assertSame($shouldBeAttributes['order'], ($matcher->numberOfInvocations() - 1));

                            return $shouldBeCraftInventoryGroup;
                        default:
                            throw new AssertionError('Number of invocations not expected.');
                    }
                }
            );

        $this->getService()->updateOrder(
            $this->craftInventoryGroupMock,
            $expectedOrder
        );
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function updateOrderExceptionTestDataProvider(): array
    {
        return [
            'test updateOrder exception' => [
                1,
                0,
                new Exception('Test')
            ]
        ];
    }

    /**
     * @dataProvider updateOrderExceptionTestDataProvider
     */
    public function testUpdateOrderException(
        int $expectedCategoryId,
        int $expectedOrder,
        Exception $expectedException
    ): void {
        $expectedCategoryModels = Collection::make([
            $this->craftInventoryGroupMock,
            $this->craftInventoryGroupMock,
            $this->craftInventoryGroupMock
        ]);

        $this->craftInventoryGroupMock->expects(self::once())
            ->method('getAttribute')
            ->with('craft_inventory_category_id')
            ->willReturn($expectedCategoryId);

        $this->craftInventoryGroupRepositoryMock->expects(self::once())
            ->method('getAllByCategoryIdOrderedByOrder')
            ->with($expectedCategoryId)
            ->willReturn($expectedCategoryModels);

        $this->inventoryResourceCalculateModelsOrderServiceMock->expects(self::once())
            ->method('getReorderedModels')
            ->with(
                $expectedCategoryModels,
                $expectedOrder,
                $this->craftInventoryGroupMock
            )->willReturn($expectedCategoryModels->all());

        $this->craftInventoryGroupRepositoryMock->expects(self::once())
            ->method('updateOrFail')
            ->willThrowException($expectedException);

        try {
            $this->getService()->updateOrder(
                $this->craftInventoryGroupMock,
                $expectedOrder
            );
        } catch (Throwable $t) {
            self::assertSame($expectedException, $t);
        }
    }

    public function testForceDeleteWithCategoryModel(): void
    {
        $this->craftInventoryGroupRepositoryMock->expects(self::exactly(2))
            ->method('forceDelete')
            ->with($this->craftInventoryGroupMock)
            ->willReturnOnConsecutiveCalls(true, false);

        self::assertTrue($this->getService()->forceDelete($this->craftInventoryGroupMock));
        self::assertFalse($this->getService()->forceDelete($this->craftInventoryGroupMock));
    }

    public function testForceDeleteWithInt(): void
    {
        $this->craftInventoryGroupRepositoryMock->expects(self::exactly(4))
            ->method('forceDelete')
            ->with($this->craftInventoryGroupMock)
            ->willReturnOnConsecutiveCalls(true, false, true, false);

        $firstId = 1;
        $secondId = 2;
        $this->craftInventoryGroupRepositoryMock->expects($matcher = self::exactly(4))
            ->method('find')
            ->willReturnCallback(function (int $id) use ($matcher, $firstId, $secondId): CraftInventoryGroup {
                switch ($matcher->numberOfInvocations()) {
                    case 1:
                    case 2:
                        self::assertSame($firstId, $id);
                        return $this->craftInventoryGroupMock;
                    case 3:
                    case 4:
                        self::assertSame($secondId, $id);
                        return $this->craftInventoryGroupMock;
                    default:
                        throw new AssertionError('Number of invocations not expected.');
                }
            });

        self::assertTrue($this->getService()->forceDelete($firstId));
        self::assertFalse($this->getService()->forceDelete($firstId));
        self::assertTrue($this->getService()->forceDelete($secondId));
        self::assertFalse($this->getService()->forceDelete($secondId));
    }
}
