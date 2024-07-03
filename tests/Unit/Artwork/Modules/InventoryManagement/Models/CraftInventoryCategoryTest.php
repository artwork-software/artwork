<?php

namespace Tests\Unit\Artwork\Modules\InventoryManagement\Models;

use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\InventoryManagement\Models\CraftInventoryCategory;
use Artwork\Modules\InventoryManagement\Models\CraftInventoryGroup;
use Illuminate\Database\Connection;
use Illuminate\Database\ConnectionResolver;
use Illuminate\Database\Query\Builder;
use PHPUnit\Framework\TestCase;

class CraftInventoryCategoryTest extends TestCase
{
    private readonly Builder $queryBuilderMock;

    private readonly CraftInventoryCategory $category;

    protected function setUp(): void
    {
        $this->queryBuilderMock = $this->getMockBuilder(Builder::class)
            ->onlyMethods(['orderBy', 'select'])
            ->disableOriginalConstructor()
            ->getMock();

        $connectionMock = $this->getMockBuilder(Connection::class)
            ->onlyMethods(['query'])
            ->disableOriginalConstructor()
            ->getMock();

        $connectionMock->expects(self::once())
            ->method('query')
            ->willReturn($this->queryBuilderMock);

        $connectionResolverMock = $this->getMockBuilder(ConnectionResolver::class)
            ->onlyMethods(['connection'])
            ->disableOriginalConstructor()
            ->getMock();

        $connectionResolverMock->expects(self::once())
            ->method('connection')
            ->with('mysql')
            ->willReturn($connectionMock);

        $this->category = new CraftInventoryCategory();
        $this->category->setConnection('mysql');
        $this->category::setConnectionResolver($connectionResolverMock);
    }

    public function testBelongsToCraft(): void
    {
        $belongsTo = $this->category->craft();

        self::assertSame('craft_id', $belongsTo->getForeignKeyName());
        self::assertSame('id', $belongsTo->getOwnerKeyName());
        self::assertSame('crafts', $belongsTo->getRelationName());
        self::assertSame(Craft::class, $belongsTo->getRelated()::class);
    }

    public function testHasManyGroups(): void
    {
        $this->queryBuilderMock->expects(self::once())
            ->method('orderBy')
            ->with('order')
            ->willReturn($this->queryBuilderMock);

        $this->queryBuilderMock->expects(self::once())
            ->method('select')
            ->with(['id', 'craft_inventory_category_id', 'name', 'order']);

        $hasMany = $this->category->groups();

        self::assertSame('id', $hasMany->getLocalKeyName());
        self::assertSame('craft_inventory_category_id', $hasMany->getForeignKeyName());
        self::assertSame(CraftInventoryGroup::class, $hasMany->getRelated()::class);
    }
}
