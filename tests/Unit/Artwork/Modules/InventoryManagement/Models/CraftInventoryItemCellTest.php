<?php

namespace Tests\Unit\Artwork\Modules\InventoryManagement\Models;

use Artwork\Modules\InventoryManagement\Models\CraftInventoryItem;
use Artwork\Modules\InventoryManagement\Models\CraftInventoryItemCell;
use Artwork\Modules\InventoryManagement\Models\CraftsInventoryColumn;
use Illuminate\Database\Connection;
use Illuminate\Database\ConnectionResolver;
use Illuminate\Database\Query\Builder;
use PHPUnit\Framework\TestCase;

class CraftInventoryItemCellTest extends TestCase
{
    private readonly Builder $queryBuilderMock;

    private readonly Connection $connectionMock;

    private readonly ConnectionResolver $connectionResolverMock;

    private readonly CraftInventoryItemCell $cell;

    protected function setUp(): void
    {
        $this->queryBuilderMock = $this->getMockBuilder(Builder::class)
            ->onlyMethods(['select'])
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

        $this->cell = new CraftInventoryItemCell();
        $this->cell->setConnection('mysql');
        $this->cell::setConnectionResolver($this->connectionResolverMock);
    }

    public function testFillable(): void
    {
        self::assertSame(
            [
                'crafts_inventory_column_id',
                'craft_inventory_item_id',
                'cell_value',
            ],
            $this->cell->getFillable()
        );
    }

    public function testBelongsToCraftInventoryItem(): void
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
            ->with(['id', 'craft_inventory_group_id', 'order']);

        $belongsTo = $this->cell->item();

        self::assertSame('craft_inventory_item_id', $belongsTo->getForeignKeyName());
        self::assertSame('id', $belongsTo->getOwnerKeyName());
        self::assertSame('craft_inventory_items', $belongsTo->getRelationName());
        self::assertSame(CraftInventoryItem::class, $belongsTo->getRelated()::class);
    }

    public function testBelongsToCraftsInventoryColumn(): void
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
            ->with(['id', 'name', 'type', 'type_options', 'background_color']);

        $belongsTo = $this->cell->column();

        self::assertSame('crafts_inventory_column_id', $belongsTo->getForeignKeyName());
        self::assertSame('id', $belongsTo->getOwnerKeyName());
        self::assertSame('crafts_inventory_columns', $belongsTo->getRelationName());
        self::assertSame(CraftsInventoryColumn::class, $belongsTo->getRelated()::class);
    }
}
