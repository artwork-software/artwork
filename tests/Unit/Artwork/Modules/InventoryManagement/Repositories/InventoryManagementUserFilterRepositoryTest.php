<?php

namespace Tests\Unit\Artwork\Modules\InventoryManagement\Repositories;

use Artwork\Modules\InventoryManagement\Models\InventoryManagementUserFilter;
use Artwork\Modules\InventoryManagement\Repositories\InventoryManagementUserFilterRepository;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as BaseBuilder;
use ReflectionClass;

class InventoryManagementUserFilterRepositoryTest extends TestCase
{
    private readonly InventoryManagementUserFilter $inventoryManagementUserFilterMock;

    private readonly Builder $eloquentBuilderMock;

    private readonly BaseBuilder $baseBuilderMock;

    /**
     * @throws Exception
     * @throws Exception
     */
    protected function setUp(): void
    {
        $this->inventoryManagementUserFilterMock = $this
            ->getMockBuilder(InventoryManagementUserFilter::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['newModelQuery', 'newEloquentBuilder', 'newBaseQueryBuilder', 'newInstance'])
            ->getMock();

        $this->eloquentBuilderMock = $this->createMock(Builder::class);
        $this->baseBuilderMock = $this->createMock(BaseBuilder::class);
    }

    private function getRepository(): InventoryManagementUserFilterRepository
    {
        return new InventoryManagementUserFilterRepository($this->inventoryManagementUserFilterMock);
    }

    private function getReflectedRepository(): ReflectionClass
    {
        return new ReflectionClass(InventoryManagementUserFilterRepository::class);
    }

    public function testConstructorHasDesiredModel(): void
    {
        self::assertSame(
            $this->getReflectedRepository()->getConstructor()->getParameters()[0]->getType()->getName(),
            InventoryManagementUserFilter::class
        );
    }

    public function testGetNewModelInstance(): void
    {
        $this->inventoryManagementUserFilterMock->expects(self::once())
            ->method('newInstance')
            ->willReturn($this->inventoryManagementUserFilterMock);

        self::assertInstanceOf(
            InventoryManagementUserFilter::class,
            $this->getRepository()->getNewModelInstance()
        );
    }

    /**
     * @throws Exception
     */
    public function testGetModelQuery(): void
    {
        $this->inventoryManagementUserFilterMock->expects(self::once())
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
    public function testGetGroupCountForCategory(): void
    {
        $this->inventoryManagementUserFilterMock->expects(self::once())
            ->method('newModelQuery')
            ->willReturn($this->baseBuilderMock);

        $this->baseBuilderMock->expects(self::once())
            ->method('where')
            ->with('user_id', 1)
            ->willReturn($this->baseBuilderMock);

        $this->baseBuilderMock->expects(self::once())
            ->method('first')
            ->willReturn($this->inventoryManagementUserFilterMock);

        self::assertInstanceOf(
            InventoryManagementUserFilter::class,
            $this->getRepository()->findForUser(1)
        );
    }

    /**
     * @throws Exception
     */
    public function testGetGroupCountForCategoryReturnsNull(): void
    {
        $this->inventoryManagementUserFilterMock->expects(self::once())
            ->method('newModelQuery')
            ->willReturn($this->baseBuilderMock);

        $this->baseBuilderMock->expects(self::once())
            ->method('where')
            ->with('user_id', 1)
            ->willReturn($this->baseBuilderMock);

        $this->baseBuilderMock->expects(self::once())
            ->method('first')
            ->willReturn(null);

        self::assertNull($this->getRepository()->findForUser(1));
    }
}
