<?php

namespace Tests\Unit\Artwork\Modules\InventoryManagement\Services;

use Artwork\Modules\InventoryManagement\Models\CraftInventoryCategory;
use Artwork\Modules\InventoryManagement\Repositories\CraftInventoryCategoryRepository;
use Artwork\Modules\InventoryManagement\Services\CraftInventoryCategoryService;
use Artwork\Modules\InventoryManagement\Services\InventoryResourceCalculateModelsOrderService;
use AssertionError;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use PHPUnit\Framework\TestCase;
use Throwable;

class CraftInventoryCategoryServiceTest extends TestCase
{
    private readonly CraftInventoryCategoryRepository $craftInventoryCategoryRepositoryMock;

    private readonly InventoryResourceCalculateModelsOrderService $inventoryResourceCalculateModelsOrderServiceMock;

    private readonly CraftInventoryCategory $craftInventoryCategoryMock;

    protected function setUp(): void
    {
        $this->craftInventoryCategoryRepositoryMock = $this
            ->getMockBuilder(CraftInventoryCategoryRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'getNewModelInstance',
                'getCategoryCountForCraft',
                'saveOrFail',
                'updateOrFail',
                'getAllByCraftIdOrderedByOrder',
                'find',
                'forceDelete'
            ])
            ->getMock();

        $this->inventoryResourceCalculateModelsOrderServiceMock = $this
            ->getMockBuilder(InventoryResourceCalculateModelsOrderService::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getReorderedModels'])
            ->getMock();

        $this->craftInventoryCategoryMock = $this->getMockBuilder(CraftInventoryCategory::class)
            ->onlyMethods(['getAttribute'])
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function getService(): CraftInventoryCategoryService
    {
        return new CraftInventoryCategoryService(
            $this->craftInventoryCategoryRepositoryMock,
            $this->inventoryResourceCalculateModelsOrderServiceMock
        );
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function createTestDataProvider(): array
    {
        $craftId = 1;
        $order = 1;
        $name = 'Test';
        return [
            'test create' => [
                $craftId,
                $order,
                $name,
                [
                    'craft_id' => $craftId,
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
        int $expectedCraftId,
        int $expectedOrder,
        string $expectedName,
        array $expectedNewModelInstanceArgs
    ): void {
        $this->craftInventoryCategoryRepositoryMock->expects(self::once())
            ->method('getNewModelInstance')
            ->with($expectedNewModelInstanceArgs)
            ->willReturn($this->craftInventoryCategoryMock);

        $this->craftInventoryCategoryRepositoryMock->expects(self::once())
            ->method('getCategoryCountForCraft')
            ->with($expectedCraftId)
            ->willReturn($expectedOrder);

        $this->craftInventoryCategoryRepositoryMock->expects(self::once())
            ->method('saveOrFail')
            ->willReturn($this->craftInventoryCategoryMock);

        $category = $this->getService()->create($expectedCraftId, $expectedName);

        self::assertInstanceOf(CraftInventoryCategory::class, $category);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function createExceptionTestDataProvider(): array
    {
        $craftId = 1;
        $order = 1;
        $name = 'Test';
        return [
            'test create' => [
                $craftId,
                $order,
                $name,
                [
                    'craft_id' => $craftId,
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
        $this->craftInventoryCategoryRepositoryMock->expects(self::once())
            ->method('getNewModelInstance')
            ->with($expectedNewModelInstanceArgs)
            ->willReturn($this->craftInventoryCategoryMock);

        $this->craftInventoryCategoryRepositoryMock->expects(self::once())
            ->method('getCategoryCountForCraft')
            ->with($expectedCraftId)
            ->willReturn($expectedOrder);

        $this->craftInventoryCategoryRepositoryMock->expects(self::once())
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
            'test create' => [
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
        $this->craftInventoryCategoryRepositoryMock->expects(self::once())
            ->method('updateOrFail')
            ->with($this->craftInventoryCategoryMock, $expectedUpdateOrFailArgs)
            ->willReturn($this->craftInventoryCategoryMock);

        $this->getService()->updateName($expectedName, $this->craftInventoryCategoryMock);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function updateNameExceptionTestDataProvider(): array
    {
        $name = 'Test';
        return [
            'test create' => [
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
        $this->craftInventoryCategoryRepositoryMock->expects(self::once())
            ->method('updateOrFail')
            ->with($this->craftInventoryCategoryMock, $expectedUpdateOrFailArgs)
            ->willThrowException($expectedException);

        try {
            $this->getService()->updateName($expectedName, $this->craftInventoryCategoryMock);
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
            [
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
        int $expectedCraftId,
        int $expectedOrder
    ): void {
        $expectedCategoryModels = Collection::make([
            $this->craftInventoryCategoryMock,
            $this->craftInventoryCategoryMock,
            $this->craftInventoryCategoryMock
        ]);

        $this->craftInventoryCategoryMock->expects(self::once())
            ->method('getAttribute')
            ->with('craft_id')
            ->willReturn($expectedCraftId);

        $this->craftInventoryCategoryRepositoryMock->expects(self::once())
            ->method('getAllByCraftIdOrderedByOrder')
            ->with($expectedCraftId)
            ->willReturn($expectedCategoryModels);

        $this->inventoryResourceCalculateModelsOrderServiceMock->expects(self::once())
            ->method('getReorderedModels')
            ->with(
                $expectedCategoryModels,
                $expectedOrder,
                $this->craftInventoryCategoryMock
            )->willReturn($expectedCategoryModels->all());

        $this->craftInventoryCategoryRepositoryMock->expects($matcher = self::exactly(3))
            ->method('updateOrFail')
            ->willReturnCallback(
                function ($shouldBeCraftInventoryCategory, $shouldBeAttributes) use ($matcher): CraftInventoryCategory {
                    switch ($matcher->numberOfInvocations()) {
                        case 1:
                        case 2:
                        case 3:
                            self::assertInstanceOf(CraftInventoryCategory::class, $shouldBeCraftInventoryCategory);
                            self::assertIsArray($shouldBeAttributes);
                            self::assertArrayHasKey('order', $shouldBeAttributes);
                            self::assertSame($shouldBeAttributes['order'], ($matcher->numberOfInvocations() - 1));

                            return $shouldBeCraftInventoryCategory;
                        default:
                            throw new AssertionError('Number of invocations not expected.');
                    }
                }
            );

        $this->getService()->updateOrder(
            $this->craftInventoryCategoryMock,
            $expectedOrder
        );
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function updateOrderExceptionTestDataProvider(): array
    {
        return [
            [
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
        int $expectedCraftId,
        int $expectedOrder,
        Exception $expectedException
    ): void {
        $expectedCategoryModels = Collection::make([
            $this->craftInventoryCategoryMock,
            $this->craftInventoryCategoryMock,
            $this->craftInventoryCategoryMock
        ]);

        $this->craftInventoryCategoryMock->expects(self::once())
            ->method('getAttribute')
            ->with('craft_id')
            ->willReturn($expectedCraftId);

        $this->craftInventoryCategoryRepositoryMock->expects(self::once())
            ->method('getAllByCraftIdOrderedByOrder')
            ->with($expectedCraftId)
            ->willReturn($expectedCategoryModels);

        $this->inventoryResourceCalculateModelsOrderServiceMock->expects(self::once())
            ->method('getReorderedModels')
            ->with(
                $expectedCategoryModels,
                $expectedOrder,
                $this->craftInventoryCategoryMock
            )->willReturn($expectedCategoryModels->all());

        $this->craftInventoryCategoryRepositoryMock->expects(self::once())
            ->method('updateOrFail')
            ->willThrowException($expectedException);

        try {
            $this->getService()->updateOrder(
                $this->craftInventoryCategoryMock,
                $expectedOrder
            );
        } catch (Throwable $t) {
            self::assertSame($expectedException, $t);
        }
    }

    public function testForceDeleteWithCategoryModel(): void
    {
        $this->craftInventoryCategoryRepositoryMock->expects(self::exactly(2))
            ->method('forceDelete')
            ->with($this->craftInventoryCategoryMock)
            ->willReturnOnConsecutiveCalls(true, false);

        self::assertTrue($this->getService()->forceDelete($this->craftInventoryCategoryMock));
        self::assertFalse($this->getService()->forceDelete($this->craftInventoryCategoryMock));
    }

    public function testForceDeleteWithInt(): void
    {
        $this->craftInventoryCategoryRepositoryMock->expects(self::exactly(4))
            ->method('forceDelete')
            ->with($this->craftInventoryCategoryMock)
            ->willReturnOnConsecutiveCalls(true, false, true, false);

        $firstId = 1;
        $secondId = 2;
        $this->craftInventoryCategoryRepositoryMock->expects($matcher = self::exactly(4))
            ->method('find')
            ->willReturnCallback(function (int $id) use ($matcher, $firstId, $secondId): CraftInventoryCategory {
                switch ($matcher->numberOfInvocations()) {
                    case 1:
                    case 2:
                        self::assertSame($firstId, $id);
                        return $this->craftInventoryCategoryMock;
                    case 3:
                    case 4:
                        self::assertSame($secondId, $id);
                        return $this->craftInventoryCategoryMock;
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
