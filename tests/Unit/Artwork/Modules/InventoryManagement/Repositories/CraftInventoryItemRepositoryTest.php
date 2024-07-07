<?php

namespace Tests\Unit\Artwork\Modules\InventoryManagement\Repositories;

use Artwork\Modules\InventoryManagement\Models\CraftInventoryItem;
use Artwork\Modules\InventoryManagement\Repositories\CraftInventoryItemRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\Builder as BaseBuilder;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class CraftInventoryItemRepositoryTest extends TestCase
{
    private readonly CraftInventoryItem $craftInventoryItemMock;

    private readonly Builder $eloquentBuilderMock;

    private readonly BaseBuilder $baseBuilderMock;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        $this->craftInventoryItemMock = $this->getMockBuilder(CraftInventoryItem::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['newModelQuery', 'newEloquentBuilder', 'newBaseQueryBuilder', 'newInstance'])
            ->getMock();

        $this->eloquentBuilderMock = $this->createMock(Builder::class);
        $this->baseBuilderMock = $this->createMock(BaseBuilder::class);
    }

    private function getRepository(): CraftInventoryItemRepository
    {
        return new CraftInventoryItemRepository($this->craftInventoryItemMock);
    }

    private function getReflectedRepository(): ReflectionClass
    {
        return new ReflectionClass(CraftInventoryItemRepository::class);
    }

    public function testConstructorHasDesiredModel(): void
    {
        self::assertSame(
            $this->getReflectedRepository()->getConstructor()->getParameters()[0]->getType()->getName(),
            CraftInventoryItem::class
        );
    }

    public function testGetNewModelInstance(): void
    {
        $this->craftInventoryItemMock->expects(self::once())
            ->method('newInstance')
            ->willReturn($this->craftInventoryItemMock);

        self::assertInstanceOf(
            CraftInventoryItem::class,
            $this->getRepository()->getNewModelInstance()
        );
    }

    /**
     * @throws Exception
     */
    public function testGetModelQuery(): void
    {
        $this->craftInventoryItemMock->expects(self::once())
            ->method('newModelQuery')
            ->willReturn($this->eloquentBuilderMock);

        self::assertInstanceOf(
            Builder::class,
            $this->getRepository()->getNewModelQuery()
        );
    }

    /**
     * @throws Exception
     */
    public function testGetAll(): void
    {
        $this->eloquentBuilderMock->expects(self::once())
            ->method('get')
            ->willReturn($this->createStub(Collection::class));

        $this->craftInventoryItemMock->expects(self::once())
            ->method('newModelQuery')
            ->willReturn($this->eloquentBuilderMock);

        $this->getRepository()->getAll(1);
    }

    public function testFind(): void
    {
        $this->eloquentBuilderMock->expects(self::once())
            ->method('find')
            ->willReturn($this->craftInventoryItemMock);

        $this->craftInventoryItemMock->expects(self::once())
            ->method('newModelQuery')
            ->willReturn($this->eloquentBuilderMock);

        self::assertInstanceOf(
            CraftInventoryItem::class,
            $this->getRepository()->find(1)
        );
    }

    public function testFindReturnsNull(): void
    {
        $this->eloquentBuilderMock->expects(self::once())
            ->method('find')
            ->willReturn(null);

        $this->craftInventoryItemMock->expects(self::once())
            ->method('newModelQuery')
            ->willReturn($this->eloquentBuilderMock);

        self::assertNull($this->getRepository()->find(1));
    }

    /**
     * @throws Exception
     */
    public function testGetAllOfGroupOrderedByOrder(): void
    {
        $this->craftInventoryItemMock->expects(self::once())
            ->method('newModelQuery')
            ->willReturn($this->baseBuilderMock);

        $this->baseBuilderMock->expects(self::once())
            ->method('where')
            ->with('craft_inventory_group_id', 1)
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
            $this->getRepository()->getAllOfGroupOrderedByOrder(1)
        );
    }
}
