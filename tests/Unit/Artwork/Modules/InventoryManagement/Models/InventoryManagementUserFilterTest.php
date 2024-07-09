<?php

namespace Tests\Unit\Artwork\Modules\InventoryManagement\Models;

use Artwork\Modules\InventoryManagement\Models\InventoryManagementUserFilter;
use Artwork\Modules\User\Models\User;
use Illuminate\Config\Repository;
use Illuminate\Database\Connection;
use Illuminate\Database\ConnectionResolver;
use Illuminate\Database\Query\Builder;
use PHPUnit\Framework\TestCase;

class InventoryManagementUserFilterTest extends TestCase
{
    private readonly Builder $queryBuilderMock;

    private readonly Connection $connectionMock;

    private readonly ConnectionResolver $connectionResolverMock;

    private readonly InventoryManagementUserFilter $filter;

    protected function setUp(): void
    {
        $this->queryBuilderMock = $this->getMockBuilder(Builder::class)
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

        $this->filter = new InventoryManagementUserFilter();
        $this->filter->setConnection('mysql');
        $this->filter::setConnectionResolver($this->connectionResolverMock);
    }

    public function testFillable(): void
    {
        self::assertSame(
            [
                'user_id',
                'filter'
            ],
            $this->filter->getFillable()
        );
    }

    public function testCasts(): void
    {
        self::assertSame(
            [
                'id' => 'int',
                'filter' => 'array'
            ],
            $this->filter->getCasts()
        );
    }

    public function testBelongsToUser(): void
    {
        $this->connectionResolverMock->expects(self::once())
            ->method('connection')
            ->with('mysql')
            ->willReturn($this->connectionMock);

        $this->connectionMock->expects(self::once())
            ->method('query')
            ->willReturn($this->queryBuilderMock);

        app()->bind(
            //bind config to prevent model instantiation from failing on non-existing config concrete
            'config',
            //as there is no need to validate any of the functions called on the repository a stub is totally fine
            //it is used by Searchable traits "function bootSearchable()" ModelObserver constructor
            fn() => $this->createStub(Repository::class)
        );
        $belongsTo = $this->filter->user();

        self::assertSame('user_id', $belongsTo->getForeignKeyName());
        self::assertSame('id', $belongsTo->getOwnerKeyName());
        self::assertSame('users', $belongsTo->getRelationName());
        self::assertSame(User::class, $belongsTo->getRelated()::class);
    }
}
