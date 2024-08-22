<?php

namespace Tests\Unit\Artwork\Modules\GlobalNotification\Models;

use Artwork\Modules\GlobalNotification\Models\GlobalNotification;
use Artwork\Modules\User\Models\User;
use Illuminate\Config\Repository;
use Illuminate\Database\Connection;
use Illuminate\Database\ConnectionResolver;
use Illuminate\Database\Query\Builder;
use PHPUnit\Framework\TestCase;

class GlobalNotificationTest extends TestCase
{
    private readonly Builder $queryBuilderMock;

    private readonly Connection $connectionMock;

    private readonly ConnectionResolver $connectionResolverMock;

    private readonly GlobalNotification $globalNotification;

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

        $this->globalNotification = new GlobalNotification();
        $this->globalNotification->setConnection('mysql');
        $this->globalNotification::setConnectionResolver($this->connectionResolverMock);
    }

    public function testFillable(): void
    {
        self::assertSame(
            [
                'title',
                'description',
                'image_name',
                'expiration_date',
                'created_by'
            ],
            $this->globalNotification->getFillable()
        );
    }

    public function testBelongsToUser(): void
    {
        $this->connectionResolverMock->expects($this->once())
            ->method('connection')
            ->with('mysql')
            ->willReturn($this->connectionMock);

        $this->connectionMock->expects($this->once())
            ->method('query')
            ->willReturn($this->queryBuilderMock);

        app()->bind(
            //bind config to prevent model instantiation from failing on non-existing config concrete
            'config',
            //as there is no need to validate any of the functions called on the repository a stub is totally fine
            //it is used by Searchable traits "function bootSearchable()" ModelObserver constructor
            fn() => $this->createStub(Repository::class)
        );

        $belongsTo = $this->globalNotification->user();

        self::assertSame('id', $belongsTo->getForeignKeyName());
        self::assertSame('created_by', $belongsTo->getOwnerKeyName());
        self::assertSame('global_notifications', $belongsTo->getRelationName());
        self::assertSame(User::class, $belongsTo->getRelated()::class);
    }
}
