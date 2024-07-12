<?php

namespace Tests\Unit\Artwork\Modules\InventoryManagement\Repositories;

use Artwork\Modules\InventoryManagement\Models\CraftInventoryItemCell;
use Artwork\Modules\InventoryManagement\Repositories\CraftInventoryItemCellRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as BaseBuilder;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class CraftInventoryItemCellRepositoryTest extends TestCase
{
    private readonly CraftInventoryItemCell $craftInventoryItemCellMock;

    private readonly Builder $eloquentBuilderMock;

    private readonly BaseBuilder $baseBuilderMock;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        $this->craftInventoryItemCellMock = $this->getMockBuilder(CraftInventoryItemCell::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['newModelQuery', 'newInstance'])
            ->getMock();

        $this->eloquentBuilderMock = $this->createMock(Builder::class);
    }

    private function getRepository(): CraftInventoryItemCellRepository
    {
        return new CraftInventoryItemCellRepository($this->craftInventoryItemCellMock);
    }

    private function getReflectedRepository(): ReflectionClass
    {
        return new ReflectionClass(CraftInventoryItemCellRepository::class);
    }

    public function testConstructorHasDesiredModel(): void
    {
        self::assertSame(
            $this->getReflectedRepository()->getConstructor()->getParameters()[0]->getType()->getName(),
            CraftInventoryItemCell::class
        );
    }

    public function testGetNewModelInstance(): void
    {
        $this->craftInventoryItemCellMock->expects(self::once())
            ->method('newInstance')
            ->willReturn($this->craftInventoryItemCellMock);

        self::assertInstanceOf(
            CraftInventoryItemCell::class,
            $this->getRepository()->getNewModelInstance()
        );
    }

    /**
     * @throws Exception
     */
    public function testGetModelQuery(): void
    {
        $this->craftInventoryItemCellMock->expects(self::once())
            ->method('newModelQuery')
            ->willReturn($this->eloquentBuilderMock);

        self::assertInstanceOf(
            Builder::class,
            $this->getRepository()->getNewModelQuery()
        );
    }
}
