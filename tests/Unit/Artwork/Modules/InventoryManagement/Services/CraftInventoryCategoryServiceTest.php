<?php

namespace Tests\Unit\Artwork\Modules\InventoryManagement\Services;

use Artwork\Modules\InventoryManagement\Models\CraftInventoryCategory;
use Artwork\Modules\InventoryManagement\Repositories\CraftInventoryCategoryRepository;
use Artwork\Modules\InventoryManagement\Services\CraftInventoryCategoryService;
use Artwork\Modules\InventoryManagement\Services\InventoryResourceCalculateModelsOrderService;
use AssertionError;
use PHPUnit\Framework\TestCase;

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
        $matcher = self::exactly(4);
        $this->craftInventoryCategoryRepositoryMock->expects($matcher)
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
                        throw new AssertionError('Parameter was not expected.');
                }
            });

        self::assertTrue($this->getService()->forceDelete($firstId));
        self::assertFalse($this->getService()->forceDelete($firstId));
        self::assertTrue($this->getService()->forceDelete($secondId));
        self::assertFalse($this->getService()->forceDelete($secondId));
    }
}
