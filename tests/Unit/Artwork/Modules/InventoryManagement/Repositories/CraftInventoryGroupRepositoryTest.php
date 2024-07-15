<?php

namespace Tests\Unit\Artwork\Modules\InventoryManagement\Repositories;

use Artwork\Modules\InventoryManagement\Models\CraftInventoryGroup;
use Artwork\Modules\InventoryManagement\Repositories\CraftInventoryGroupRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\Builder as BaseBuilder;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class CraftInventoryGroupRepositoryTest extends TestCase
{
    private readonly CraftInventoryGroup $craftInventoryGroupMock;

    private readonly Builder $eloquentBuilderMock;

    private readonly BaseBuilder $baseBuilderMock;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        $this->craftInventoryGroupMock = $this->getMockBuilder(CraftInventoryGroup::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['newModelQuery', 'newEloquentBuilder', 'newBaseQueryBuilder', 'newInstance'])
            ->getMock();

        $this->eloquentBuilderMock = $this->createMock(Builder::class);
        $this->baseBuilderMock = $this->createMock(BaseBuilder::class);
    }

    private function getRepository(): CraftInventoryGroupRepository
    {
        return new CraftInventoryGroupRepository($this->craftInventoryGroupMock);
    }

    private function getReflectedRepository(): ReflectionClass
    {
        return new ReflectionClass(CraftInventoryGroupRepository::class);
    }

    public function testConstructorHasDesiredModel(): void
    {
        self::assertSame(
            $this->getReflectedRepository()->getConstructor()->getParameters()[0]->getType()->getName(),
            CraftInventoryGroup::class
        );
    }

    public function testGetNewModelInstance(): void
    {
        $this->craftInventoryGroupMock->expects(self::once())
            ->method('newInstance')
            ->willReturn($this->craftInventoryGroupMock);

        self::assertInstanceOf(
            CraftInventoryGroup::class,
            $this->getRepository()->getNewModelInstance()
        );
    }

    /**
     * @throws Exception
     */
    public function testGetModelQuery(): void
    {
        $this->craftInventoryGroupMock->expects(self::once())
            ->method('newModelQuery')
            ->willReturn($this->eloquentBuilderMock);

        self::assertInstanceOf(
            Builder::class,
            $this->getRepository()->getNewModelQuery()
        );
    }

    public function testFind(): void
    {
        $this->eloquentBuilderMock->expects(self::once())
            ->method('find')
            ->willReturn($this->craftInventoryGroupMock);

        $this->craftInventoryGroupMock->expects(self::once())
            ->method('newModelQuery')
            ->willReturn($this->eloquentBuilderMock);

        self::assertInstanceOf(
            CraftInventoryGroup::class,
            $this->getRepository()->find(1)
        );
    }

    public function testFindReturnsNull(): void
    {
        $this->eloquentBuilderMock->expects(self::once())
            ->method('find')
            ->willReturn(null);

        $this->craftInventoryGroupMock->expects(self::once())
            ->method('newModelQuery')
            ->willReturn($this->eloquentBuilderMock);

        self::assertNull($this->getRepository()->find(1));
    }

    /**
     * @throws Exception
     */
    public function testGetAllByCategoryIdOrderedByOrder(): void
    {
        $this->craftInventoryGroupMock->expects(self::once())
            ->method('newModelQuery')
            ->willReturn($this->baseBuilderMock);

        $this->baseBuilderMock->expects(self::once())
            ->method('where')
            ->with('craft_inventory_category_id', 1)
            ->willReturn($this->baseBuilderMock);

        $this->baseBuilderMock->expects(self::once())
            ->method('orderBy')
            ->with('order')
            ->willReturn($this->baseBuilderMock);

        $this->baseBuilderMock->expects(self::once())
            ->method('get')
            ->willReturn(
                $this->createStub(Collection::class)
            );

        self::assertInstanceOf(
            Collection::class,
            $this->getRepository()->getAllByCategoryIdOrderedByOrder(1)
        );
    }

    /**
     * @throws Exception
     */
    public function testGetGroupCountForCategory(): void
    {
        $this->craftInventoryGroupMock->expects(self::once())
            ->method('newModelQuery')
            ->willReturn($this->baseBuilderMock);

        $this->baseBuilderMock->expects(self::once())
            ->method('where')
            ->with('craft_inventory_category_id', 1)
            ->willReturn($this->baseBuilderMock);

        $this->baseBuilderMock->expects(self::once())
            ->method('count')
            ->willReturn(1);

        self::assertSame(
            1,
            $this->getRepository()->getGroupCountForCategory(1)
        );
    }
}
