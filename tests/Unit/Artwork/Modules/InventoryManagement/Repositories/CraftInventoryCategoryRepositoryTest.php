<?php

namespace Tests\Unit\Artwork\Modules\InventoryManagement\Repositories;

use Artwork\Modules\InventoryManagement\Models\CraftInventoryCategory;
use Artwork\Modules\InventoryManagement\Repositories\CraftInventoryCategoryRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as BaseBuilder;
use Illuminate\Database\Eloquent\Collection;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class CraftInventoryCategoryRepositoryTest extends TestCase
{
    private readonly CraftInventoryCategory $craftInventoryCategoryMock;

    private readonly Builder $eloquentBuilderMock;

    private readonly BaseBuilder $baseBuilderMock;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        $this->craftInventoryCategoryMock = $this->getMockBuilder(CraftInventoryCategory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['newModelQuery', 'newEloquentBuilder', 'newBaseQueryBuilder', 'newInstance'])
            ->getMock();

        $this->eloquentBuilderMock = $this->createMock(Builder::class);
        $this->baseBuilderMock = $this->createMock(BaseBuilder::class);
    }

    private function getRepository(): CraftInventoryCategoryRepository
    {
        return new CraftInventoryCategoryRepository($this->craftInventoryCategoryMock);
    }

    private function getReflectedRepository(): ReflectionClass
    {
        return new ReflectionClass(CraftInventoryCategoryRepository::class);
    }

    public function testConstructorHasDesiredModel(): void
    {
        self::assertSame(
            $this->getReflectedRepository()->getConstructor()->getParameters()[0]->getType()->getName(),
            CraftInventoryCategory::class
        );
    }

    public function testGetNewModelInstance(): void
    {
        $this->craftInventoryCategoryMock->expects(self::once())
            ->method('newInstance')
            ->willReturn($this->craftInventoryCategoryMock);

        self::assertInstanceOf(
            CraftInventoryCategory::class,
            $this->getRepository()->getNewModelInstance()
        );
    }

    /**
     * @throws Exception
     */
    public function testGetModelQuery(): void
    {
        $this->craftInventoryCategoryMock->expects(self::once())
            ->method('newModelQuery')
            ->willReturn($this->eloquentBuilderMock);

        $query = $this->getRepository()->getNewModelQuery();
        self::assertInstanceOf(Builder::class, $query);
    }

    public function testFind(): void
    {
        $this->eloquentBuilderMock->expects(self::once())
            ->method('find')
            ->willReturn($this->craftInventoryCategoryMock);

        $this->craftInventoryCategoryMock->expects(self::once())
            ->method('newModelQuery')
            ->willReturn($this->eloquentBuilderMock);

        $this->getRepository()->find(1);
    }

    /**
     * @throws Exception
     */
    public function testGetAllByCraftIdOrderedByOrder(): void
    {
        $this->craftInventoryCategoryMock->expects(self::once())
            ->method('newModelQuery')
            ->willReturn($this->baseBuilderMock);

        $this->baseBuilderMock->expects(self::once())
            ->method('where')
            ->with('craft_id', 1)
            ->willReturn($this->baseBuilderMock);

        $this->baseBuilderMock->expects(self::once())
            ->method('orderBy')
            ->with('order')
            ->willReturn($this->baseBuilderMock);

        $this->baseBuilderMock->expects(self::once())
            ->method('get')
            ->willReturn(
                $this->createMock(Collection::class)
            );

        $this->getRepository()->getAllByCraftIdOrderedByOrder(1);
    }

    public function testGetCategoryCountForCraft(): void
    {
        $this->craftInventoryCategoryMock->expects(self::once())
            ->method('newModelQuery')
            ->willReturn($this->baseBuilderMock);

        $this->baseBuilderMock->expects(self::once())
            ->method('where')
            ->with('craft_id', 1)
            ->willReturn($this->baseBuilderMock);

        $this->baseBuilderMock->expects(self::once())
            ->method('count')
            ->willReturn(1);

        $result = $this->getRepository()->getCategoryCountForCraft(1);

        self::assertSame(1, $result);
    }
}
