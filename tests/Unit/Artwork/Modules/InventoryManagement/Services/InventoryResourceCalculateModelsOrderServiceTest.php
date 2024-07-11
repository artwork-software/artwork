<?php

namespace Tests\Unit\Artwork\Modules\InventoryManagement\Services;

use Artwork\Modules\InventoryManagement\Models\CraftInventoryCategory;
use Artwork\Modules\InventoryManagement\Services\InventoryResourceCalculateModelsOrderService;
use Illuminate\Database\Eloquent\Collection;
use PHPUnit\Framework\TestCase;

class InventoryResourceCalculateModelsOrderServiceTest extends TestCase
{
    public function testGetReorderedModelsMoveToEndFromStart(): void
    {
        $movingModel = $this->getMockBuilder(CraftInventoryCategory::class)
            ->onlyMethods(['getAttribute'])
            ->disableOriginalConstructor()
            ->getMock();

        $movingModel->expects(self::exactly(4))
            ->method('getAttribute')
            ->with('id')
            ->willReturn(1);

        $firstStaticModel = $this->getMockBuilder(CraftInventoryCategory::class)
            ->onlyMethods(['getAttribute'])
            ->disableOriginalConstructor()
            ->getMock();

        $firstStaticModel->expects(self::once())
            ->method('getAttribute')
            ->with('id')
            ->willReturn(2);

        $secondStaticModel = $this->getMockBuilder(CraftInventoryCategory::class)
            ->onlyMethods(['getAttribute'])
            ->disableOriginalConstructor()
            ->getMock();

        $secondStaticModel->expects(self::once())
            ->method('getAttribute')
            ->with('id')
            ->willReturn(3);

        $modelsToOrder = Collection::make([
            $movingModel,
            $firstStaticModel,
            $secondStaticModel
        ]);

        $expectedResult = Collection::make([
            $firstStaticModel,
            $secondStaticModel,
            $movingModel,
        ]);

        self::assertSame(
            $expectedResult->all(),
            (new InventoryResourceCalculateModelsOrderService())->getReorderedModels(
                $modelsToOrder,
                3,
                $movingModel
            )
        );
    }

    public function testGetReorderedModelsMoveBetweenFromStart(): void
    {
        $movingModel = $this->getMockBuilder(CraftInventoryCategory::class)
            ->onlyMethods(['getAttribute'])
            ->disableOriginalConstructor()
            ->getMock();

        $movingModel->expects(self::exactly(4))
            ->method('getAttribute')
            ->with('id')
            ->willReturn(1);

        $firstStaticModel = $this->getMockBuilder(CraftInventoryCategory::class)
            ->onlyMethods(['getAttribute'])
            ->disableOriginalConstructor()
            ->getMock();

        $firstStaticModel->expects(self::once())
            ->method('getAttribute')
            ->with('id')
            ->willReturn(2);

        $secondStaticModel = $this->getMockBuilder(CraftInventoryCategory::class)
            ->onlyMethods(['getAttribute'])
            ->disableOriginalConstructor()
            ->getMock();

        $secondStaticModel->expects(self::once())
            ->method('getAttribute')
            ->with('id')
            ->willReturn(3);

        $modelsToOrder = Collection::make([
            $movingModel,
            $firstStaticModel,
            $secondStaticModel
        ]);

        $expectedResult = Collection::make([
            $firstStaticModel,
            $movingModel,
            $secondStaticModel
        ]);

        self::assertSame(
            $expectedResult->all(),
            (new InventoryResourceCalculateModelsOrderService())->getReorderedModels(
                $modelsToOrder,
                2,
                $movingModel
            )
        );
    }

    public function testGetReorderedModelsMoveToStartFromEnd(): void
    {
        $movingModel = $this->getMockBuilder(CraftInventoryCategory::class)
            ->onlyMethods(['getAttribute'])
            ->disableOriginalConstructor()
            ->getMock();

        $movingModel->expects(self::exactly(4))
            ->method('getAttribute')
            ->with('id')
            ->willReturn(1);

        $firstStaticModel = $this->getMockBuilder(CraftInventoryCategory::class)
            ->onlyMethods(['getAttribute'])
            ->disableOriginalConstructor()
            ->getMock();

        $firstStaticModel->expects(self::once())
            ->method('getAttribute')
            ->with('id')
            ->willReturn(2);

        $secondStaticModel = $this->getMockBuilder(CraftInventoryCategory::class)
            ->onlyMethods(['getAttribute'])
            ->disableOriginalConstructor()
            ->getMock();

        $secondStaticModel->expects(self::once())
            ->method('getAttribute')
            ->with('id')
            ->willReturn(3);

        $modelsToOrder = Collection::make([
            $firstStaticModel,
            $secondStaticModel,
            $movingModel,
        ]);

        $expectedResult = Collection::make([
            $movingModel,
            $firstStaticModel,
            $secondStaticModel
        ]);

        self::assertSame(
            $expectedResult->all(),
            (new InventoryResourceCalculateModelsOrderService())->getReorderedModels(
                $modelsToOrder,
                0,
                $movingModel
            )
        );
    }

    public function testGetReorderedModelsMoveBetweenFromEnd(): void
    {
        $movingModel = $this->getMockBuilder(CraftInventoryCategory::class)
            ->onlyMethods(['getAttribute'])
            ->disableOriginalConstructor()
            ->getMock();

        $movingModel->expects(self::exactly(4))
            ->method('getAttribute')
            ->with('id')
            ->willReturn(1);

        $firstStaticModel = $this->getMockBuilder(CraftInventoryCategory::class)
            ->onlyMethods(['getAttribute'])
            ->disableOriginalConstructor()
            ->getMock();

        $firstStaticModel->expects(self::once())
            ->method('getAttribute')
            ->with('id')
            ->willReturn(2);

        $secondStaticModel = $this->getMockBuilder(CraftInventoryCategory::class)
            ->onlyMethods(['getAttribute'])
            ->disableOriginalConstructor()
            ->getMock();

        $secondStaticModel->expects(self::once())
            ->method('getAttribute')
            ->with('id')
            ->willReturn(3);

        $modelsToOrder = Collection::make([
            $firstStaticModel,
            $secondStaticModel,
            $movingModel,
        ]);

        $expectedResult = Collection::make([
            $firstStaticModel,
            $movingModel,
            $secondStaticModel
        ]);

        self::assertSame(
            $expectedResult->all(),
            (new InventoryResourceCalculateModelsOrderService())->getReorderedModels(
                $modelsToOrder,
                1,
                $movingModel
            )
        );
    }
}
