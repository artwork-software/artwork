<?php

namespace Tests\Unit\Artwork\Modules\InventoryScheduling\Repository;

use Artwork\Modules\InventoryManagement\Models\CraftInventoryItem;
use Artwork\Modules\InventoryManagement\Repositories\CraftInventoryItemRepository;
use Illuminate\Database\Eloquent\Builder;
use \Illuminate\Database\Query\Builder as BaseBuilder;
use Illuminate\Database\Eloquent\Collection;
use PHPUnit\Framework\TestCase;
use Throwable;

class CraftInventoryItemRepositoryTest extends TestCase
{
    private readonly CraftInventoryItem $craftInventoryItemMock;

    protected function setUp(): void
    {
        $this->craftInventoryItemMock = $this->getMockBuilder(CraftInventoryItem::class)
            ->onlyMethods(['newModelQuery', 'newInstance'])
            ->disableOriginalConstructor()
            ->getMock();
    }

    private function getRepository(): CraftInventoryItemRepository
    {
        return new CraftInventoryItemRepository(
            $this->craftInventoryItemMock
        );
    }

    /**
     * @return array<string, array<int|string, mixed>>
     */
    public static function getNewModelInstanceTestDataProvider(): array
    {
        return [
            'test with empty attributes' => [
                [],
            ]
        ];
    }

    /**
     * @dataProvider getNewModelInstanceTestDataProvider
     */
    public function testGetNewModelInstance(
        $expectedAttributes
    ): void {
        $this->craftInventoryItemMock->expects(self::once())
            ->method('newInstance')
            ->with($expectedAttributes)
            ->willReturnSelf();

        $this->getRepository()->getNewModelInstance($expectedAttributes);
    }

    /**
     * @throws Throwable
     */
    public function testGetNewModelQuery(): void
    {
        $this->craftInventoryItemMock->expects(self::once())
            ->method('newModelQuery')
            ->willReturn($this->createStub(Builder::class));

        $this->getRepository()->getNewModelQuery();
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    public static function getAllTestDataProvider(): array
    {
        return [
            [
                Collection::make()
            ]
        ];
    }

    /**
     * @dataProvider getAllTestDataProvider
     * @throws Throwable
     */
    public function testGetAll(Collection $expectedCollection): void
    {
        $builderMock = $this->getMockBuilder(Builder::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['get'])
            ->getMock();

        $this->craftInventoryItemMock->expects(self::once())
            ->method('newModelQuery')
            ->willReturn($builderMock);

        $builderMock->expects(self::once())
            ->method('get')
            ->willReturn($expectedCollection);

        $collection = $this->getRepository()->getAll();

        $this->assertSame($collection, $expectedCollection);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function findTestDataProvider(): array
    {
        return [
            'test return model' => [
                1,
                new CraftInventoryItem()
            ],
            'test return null' => [
                1,
                null
            ]
        ];
    }

    /**
     * @dataProvider findTestDataProvider
     */
    public function testFind(
        int $expectedId,
        CraftInventoryItem|null $expectedFindReturn
    ): void {
        $builderMock = $this->getMockBuilder(Builder::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['find'])
            ->getMock();

        $this->craftInventoryItemMock->expects(self::once())
            ->method('newModelQuery')
            ->willReturn($builderMock);

        $builderMock->expects(self::once())
            ->method('find')
            ->willReturn($expectedFindReturn);

        $result = $this->getRepository()->find($expectedId);

        $this->assertSame($expectedFindReturn, $result);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function getAllOfGroupOrderedByOrderTestDataProvider(): array
    {
        return [
            [
                1,
                'craft_inventory_group_id',
                'order',
                Collection::make()
            ]
        ];
    }

    /**
     * @dataProvider getAllOfGroupOrderedByOrderTestDataProvider
     */
    public function testGetAllOfGroupOrderedByOrder(
        int $expectedId,
        string $expectedGroupIdColumn,
        string $expectedOrderColumn,
        Collection $expectedCollection
    ): void {
        $builderMock = $this->getMockBuilder(Builder::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['find', 'where'])
            ->getMock();

        $baseBuilderMock = $this->getMockBuilder(BaseBuilder::class)
            ->onlyMethods(['orderBy', 'get'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->craftInventoryItemMock->expects($this->once())
            ->method('newModelQuery')
            ->willReturn($builderMock);

        $builderMock->expects($this->once())
            ->method('where')
            ->with($expectedGroupIdColumn, $expectedId)
            ->willReturn($baseBuilderMock);

        $baseBuilderMock->expects($this->once())
            ->method('orderBy')
            ->with($expectedOrderColumn)
            ->willReturnSelf();

        $baseBuilderMock->expects($this->once())
            ->method('get')
            ->willReturn($expectedCollection);

        $this->assertSame(
            $expectedCollection,
            $this->getRepository()->getAllOfGroupOrderedByOrder($expectedId)
        );
    }
}
