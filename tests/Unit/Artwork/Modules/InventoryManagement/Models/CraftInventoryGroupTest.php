<?php

namespace Tests\Unit\Artwork\Modules\InventoryManagement\Models;

use Artwork\Modules\InventoryManagement\Models\CraftInventoryCategory;
use Artwork\Modules\InventoryManagement\Models\CraftInventoryGroup;
use Artwork\Modules\InventoryManagement\Models\CraftInventoryItem;
use Illuminate\Database\Connection;
use Illuminate\Database\ConnectionResolver;
use Illuminate\Database\Query\Builder;
use PHPUnit\Framework\TestCase;

class CraftInventoryGroupTest extends TestCase
{
    private readonly Builder $queryBuilderMock;

    private readonly Connection $connectionMock;

    private readonly ConnectionResolver $connectionResolverMock;

    private readonly CraftInventoryGroup $group;

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

        $this->group = new CraftInventoryGroup();
        $this->group->setConnection('mysql');
        $this->group::setConnectionResolver($this->connectionResolverMock);
    }

    public function testFillable(): void
    {
        self::assertSame(
            [
                'craft_inventory_category_id',
                'name',
                'order',
            ],
            $this->group->getFillable()
        );
    }

    public function testBelongsToCraftInventoryCategory(): void
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
            ->with(['id', 'craft_id', 'name', 'order']);

        $belongsTo = $this->group->category();

        self::assertSame('craft_inventory_category_id', $belongsTo->getForeignKeyName());
        self::assertSame('id', $belongsTo->getOwnerKeyName());
        self::assertSame('craft_inventory_categories', $belongsTo->getRelationName());
        self::assertSame(CraftInventoryCategory::class, $belongsTo->getRelated()::class);
    }

    public function testHasManyCraftInventoryItems(): void
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
            ->with('order')
            ->willReturn($this->queryBuilderMock);

        $this->queryBuilderMock->expects(self::once())
            ->method('select')
            ->with(['id', 'craft_inventory_group_id', 'order']);

        $hasMany = $this->group->items();

        self::assertSame('id', $hasMany->getLocalKeyName());
        self::assertSame('craft_inventory_group_id', $hasMany->getForeignKeyName());
        self::assertSame(CraftInventoryItem::class, $hasMany->getRelated()::class);
    }
}
