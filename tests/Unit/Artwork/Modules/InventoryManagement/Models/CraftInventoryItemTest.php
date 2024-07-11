<?php

namespace Tests\Unit\Artwork\Modules\InventoryManagement\Models;

use Artwork\Modules\InventoryManagement\Models\CraftInventoryGroup;
use Artwork\Modules\InventoryManagement\Models\CraftInventoryItem;
use Artwork\Modules\InventoryManagement\Models\CraftInventoryItemCell;
use Artwork\Modules\InventoryScheduling\Models\CraftInventoryItemEvent;
use Illuminate\Database\Connection;
use Illuminate\Database\ConnectionResolver;
use Illuminate\Database\Query\Builder;
use PHPUnit\Framework\TestCase;

class CraftInventoryItemTest extends TestCase
{
    private readonly Builder $queryBuilderMock;

    private readonly Connection $connectionMock;

    private readonly ConnectionResolver $connectionResolverMock;

    private readonly CraftInventoryItem $item;

    protected function setUp(): void
    {
        $this->queryBuilderMock = $this->getMockBuilder(Builder::class)
            ->onlyMethods(['orderBy', 'select'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->connectionMock = $this->getMockBuilder(Connection::class)
            ->onlyMethods(['query'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->connectionResolverMock = $this->getMockBuilder(ConnectionResolver::class)
            ->onlyMethods(['connection'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->item = new CraftInventoryItem();
        $this->item->setConnection('mysql');
        $this->item::setConnectionResolver($this->connectionResolverMock);
    }

    public function testFillable(): void
    {
        self::assertSame(
            [
                'craft_inventory_group_id',
                'order',
            ],
            $this->item->getFillable()
        );
    }

    public function testBelongsToCraftInventoryGroup(): void
    {
        $this->connectionResolverMock->expects(self::once())
            ->method('connection')
            ->with('mysql')
            ->willReturn($this->connectionMock);

        $this->connectionMock->expects(self::once())
            ->method('query')
            ->willReturn($this->queryBuilderMock);

        $this->queryBuilderMock->expects(self::once())
            ->method('select')
            ->with(['id', 'craft_inventory_category_id', 'name', 'order']);

        $belongsTo = $this->item->group();

        self::assertSame('craft_inventory_group_id', $belongsTo->getForeignKeyName());
        self::assertSame('id', $belongsTo->getOwnerKeyName());
        self::assertSame('craft_inventory_categories', $belongsTo->getRelationName());
        self::assertSame(CraftInventoryGroup::class, $belongsTo->getRelated()::class);
    }

    public function testHasManyCraftInventoryItemCells(): void
    {
        $this->connectionResolverMock->expects(self::once())
            ->method('connection')
            ->with('mysql')
            ->willReturn($this->connectionMock);

        $this->connectionMock->expects(self::once())
            ->method('query')
            ->willReturn($this->queryBuilderMock);

        $this->queryBuilderMock->expects(self::once())
            ->method('orderBy')
            ->with('id')
            ->willReturn($this->queryBuilderMock);

        $this->queryBuilderMock->expects(self::once())
            ->method('select')
            ->with(['id', 'crafts_inventory_column_id', 'craft_inventory_item_id', 'cell_value']);

        $hasMany = $this->item->cells();

        self::assertSame('id', $hasMany->getLocalKeyName());
        self::assertSame('craft_inventory_item_id', $hasMany->getForeignKeyName());
        self::assertSame(CraftInventoryItemCell::class, $hasMany->getRelated()::class);
    }

    public function testHasManyCraftInventoryItemEvents(): void
    {
        $this->connectionResolverMock->expects(self::once())
            ->method('connection')
            ->with('mysql')
            ->willReturn($this->connectionMock);

        $this->connectionMock->expects(self::once())
            ->method('query')
            ->willReturn($this->queryBuilderMock);

        $hasMany = $this->item->events();

        self::assertSame('id', $hasMany->getLocalKeyName());
        self::assertSame('craft_inventory_item_id', $hasMany->getForeignKeyName());
        self::assertSame(CraftInventoryItemEvent::class, $hasMany->getRelated()::class);
    }
}
