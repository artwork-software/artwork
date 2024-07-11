<?php

namespace Tests\Unit\Artwork\Modules\InventoryManagement\Models;

use Artwork\Modules\InventoryManagement\Models\CraftInventoryItemCell;
use Artwork\Modules\InventoryManagement\Models\CraftsInventoryColumn;
use Illuminate\Database\Connection;
use Illuminate\Database\ConnectionResolver;
use Illuminate\Database\Query\Builder;
use PHPUnit\Framework\TestCase;

class CraftsInventoryColumnTest extends TestCase
{
    private readonly Builder $queryBuilderMock;

    private readonly Connection $connectionMock;

    private readonly ConnectionResolver $connectionResolverMock;

    private readonly CraftsInventoryColumn $column;

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

        $this->column = new CraftsInventoryColumn();
        $this->column->setConnection('mysql');
        $this->column::setConnectionResolver($this->connectionResolverMock);
    }

    public function testFillable(): void
    {
        self::assertSame(
            [
                'name',
                'type',
                'type_options',
                'background_color'
            ],
            $this->column->getFillable()
        );
    }

    public function testCasts(): void
    {
        self::assertSame(
            [
                'id' => 'int',
                'type_options' => 'array'
            ],
            $this->column->getCasts()
        );
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
            ->method('select')
            ->with(['id', 'crafts_inventory_column_id', 'craft_inventory_item_id', 'cell_value']);

        $hasMany = $this->column->cells();

        self::assertSame('id', $hasMany->getLocalKeyName());
        self::assertSame('crafts_inventory_column_id', $hasMany->getForeignKeyName());
        self::assertSame(CraftInventoryItemCell::class, $hasMany->getRelated()::class);
    }
}
