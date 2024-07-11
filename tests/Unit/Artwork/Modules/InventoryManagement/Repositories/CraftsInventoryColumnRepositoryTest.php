<?php

namespace Tests\Unit\Artwork\Modules\InventoryManagement\Repositories;

use Artwork\Modules\InventoryManagement\Models\CraftsInventoryColumn;
use Artwork\Modules\InventoryManagement\Repositories\CraftsInventoryColumnRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as BaseBuilder;
use ReflectionClass;

class CraftsInventoryColumnRepositoryTest extends TestCase
{
    private readonly CraftsInventoryColumn $craftsInventoryColumn;

    private readonly Builder $eloquentBuilderMock;

    private readonly BaseBuilder $baseBuilderMock;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        $this->craftsInventoryColumn = $this->getMockBuilder(CraftsInventoryColumn::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'newModelQuery',
                'newEloquentBuilder',
                'newBaseQueryBuilder',
                'newInstance',
                'cells'
            ])
            ->getMock();

        $this->eloquentBuilderMock = $this->createMock(Builder::class);
        $this->baseBuilderMock = $this->createMock(BaseBuilder::class);
    }

    private function getRepository(): CraftsInventoryColumnRepository
    {
        return new CraftsInventoryColumnRepository($this->craftsInventoryColumn);
    }

    private function getReflectedRepository(): ReflectionClass
    {
        return new ReflectionClass(CraftsInventoryColumnRepository::class);
    }

    public function testConstructorHasDesiredModel(): void
    {
        self::assertSame(
            $this->getReflectedRepository()->getConstructor()->getParameters()[0]->getType()->getName(),
            CraftsInventoryColumn::class
        );
    }

    public function testGetNewModelInstance(): void
    {
        $this->craftsInventoryColumn->expects(self::once())
            ->method('newInstance')
            ->willReturn($this->craftsInventoryColumn);

        self::assertInstanceOf(
            CraftsInventoryColumn::class,
            $this->getRepository()->getNewModelInstance()
        );
    }

    /**
     * @throws Exception
     */
    public function testGetModelQuery(): void
    {
        $this->craftsInventoryColumn->expects(self::once())
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
            ->willReturn($this->craftsInventoryColumn);

        $this->craftsInventoryColumn->expects(self::once())
            ->method('newModelQuery')
            ->willReturn($this->eloquentBuilderMock);

        self::assertInstanceOf(
            CraftsInventoryColumn::class,
            $this->getRepository()->find(1)
        );
    }

    public function testFindReturnsNull(): void
    {
        $this->eloquentBuilderMock->expects(self::once())
            ->method('find')
            ->willReturn(null);

        $this->craftsInventoryColumn->expects(self::once())
            ->method('newModelQuery')
            ->willReturn($this->eloquentBuilderMock);

        self::assertNull($this->getRepository()->find(1));
    }

    /**
     * @throws Exception
     */
    public function testGetAllByCategoryIdOrderedByOrder(): void
    {
        $this->craftsInventoryColumn->expects(self::once())
            ->method('newModelQuery')
            ->willReturn($this->baseBuilderMock);

        $this->baseBuilderMock->expects(self::once())
            ->method('orderBy')
            ->with('id', 'asc')
            ->willReturn($this->baseBuilderMock);

        $this->baseBuilderMock->expects(self::once())
            ->method('get')
            ->with(['id', 'name', 'type', 'type_options', 'background_color'])
            ->willReturn(
                $this->createStub(Collection::class)
            );

        self::assertInstanceOf(
            Collection::class,
            $this->getRepository()->getAllOrdered()
        );
    }

    /**
     * @throws Exception
     */
    public function testGetAllByCategoryIdOrderedByOrderPassesOrderParametersWithoutChanges(): void
    {
        $this->craftsInventoryColumn->expects(self::once())
            ->method('newModelQuery')
            ->willReturn($this->baseBuilderMock);

        $this->baseBuilderMock->expects(self::once())
            ->method('orderBy')
            ->with('abc', 'def')
            ->willReturn($this->baseBuilderMock);

        $this->baseBuilderMock->expects(self::once())
            ->method('get')
            ->with(['id', 'name', 'type', 'type_options', 'background_color'])
            ->willReturn(
                $this->createStub(Collection::class)
            );

        self::assertInstanceOf(
            Collection::class,
            $this->getRepository()->getAllOrdered('abc', 'def')
        );
    }

    public function testGetAllItemCells(): void
    {
        $hasManyMock = $this->getMockBuilder(HasMany::class)
            ->onlyMethods(['get'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->craftsInventoryColumn->expects(self::once())
            ->method('cells')
            ->willReturn($hasManyMock);

        $hasManyMock->expects(self::once())
            ->method('get')
            ->willReturn($this->createStub(Collection::class));

        self::assertInstanceOf(
            Collection::class,
            $this->getRepository()->getAllItemCells($this->craftsInventoryColumn)
        );
    }
}
