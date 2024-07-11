<?php

namespace Tests\Unit\Artwork\Modules\InventoryScheduling\Models;

use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\InventoryManagement\Models\CraftInventoryItem;
use Artwork\Modules\InventoryScheduling\Models\CraftInventoryItemEvent;
use Artwork\Modules\User\Models\User;
use Illuminate\Config\Repository;
use Illuminate\Database\Connection;
use Illuminate\Database\ConnectionResolver;
use Illuminate\Support\Facades\Facade;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Illuminate\Database\Query\Builder;

class CraftInventoryItemEventTest extends TestCase
{
    private readonly ConnectionResolver $connectionResolverMock;

    private readonly Connection $connectionMock;

    private readonly Builder $builderMock;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        $this->connectionResolverMock = $this->getMockBuilder(ConnectionResolver::class)
            ->onlyMethods(['connection'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->connectionMock = $this->getMockBuilder(Connection::class)
            ->onlyMethods(['query'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->builderMock = $this->getMockBuilder(Builder::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    private function getModel(): CraftInventoryItemEvent
    {
        $model = new CraftInventoryItemEvent();

        $model->setConnection('mysql');
        $model::setConnectionResolver($this->connectionResolverMock);

        return $model;
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    public static function fillableTestDataProvider(): array
    {
        return [
            [
                [
                    'craft_inventory_item_id',
                    'event_id',
                    'quantity',
                    'comment',
                    'start',
                    'end',
                    'is_all_day',
                    'user_id',
                ]
            ]
        ];
    }

    /**
     * @dataProvider fillableTestDataProvider
     */
    public function testFillable(array $expectedFillable): void
    {
        $this->assertSame($expectedFillable, $this->getModel()->getFillable());
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    public static function castsTestDataProvider(): array
    {
        return [
            [
                [
                    'id' => 'int',
                    'start' => 'datetime',
                    'end' => 'datetime',
                    'is_all_day' => 'boolean',
                ]
            ]
        ];
    }

    /**
     * @dataProvider castsTestDataProvider
     */
    public function testCasts(array $expectedCasts): void
    {
        $this->assertSame($expectedCasts, $this->getModel()->getCasts());
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    public static function itemTestDataProvider(): array
    {
        return [
            [
                CraftInventoryItem::class,
                'craft_inventory_item_id',
                'id',
                'craft_inventory_items'
            ]
        ];
    }

    /**
     * @dataProvider itemTestDataProvider
     */
    public function testItem(
        string $expectedRelated,
        string $expectedForeignKey,
        string $expectedOwnerKey,
        string $expectedRelation
    ): void {
        $this->connectionResolverMock->expects(self::once())
            ->method('connection')
            ->with('mysql')
            ->willReturn($this->connectionMock);

        $this->connectionMock->expects(self::once())
            ->method('query')
            ->wilLReturn($this->builderMock);

        $belongsTo = $this->getModel()->item();

        $this->assertSame($expectedRelated, $belongsTo->getRelated()::class);
        $this->assertSame($expectedForeignKey, $belongsTo->getForeignKeyName());
        $this->assertSame($expectedOwnerKey, $belongsTo->getOwnerKeyName());
        $this->assertSame($expectedRelation, $belongsTo->getRelationName());
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    public static function eventTestDataProvider(): array
    {
        return [
            [
                Event::class,
                'event_id',
                'id',
                'events'
            ]
        ];
    }

    /**
     * @dataProvider eventTestDataProvider
     */
    public function testEvent(
        string $expectedRelated,
        string $expectedForeignKey,
        string $expectedOwnerKey,
        string $expectedRelation
    ): void {
        $this->connectionResolverMock->expects(self::once())
            ->method('connection')
            ->with('mysql')
            ->willReturn($this->connectionMock);

        $this->connectionMock->expects(self::once())
            ->method('query')
            ->wilLReturn($this->builderMock);

        $belongsTo = $this->getModel()->event();

        $this->assertSame($expectedRelated, $belongsTo->getRelated()::class);
        $this->assertSame($expectedForeignKey, $belongsTo->getForeignKeyName());
        $this->assertSame($expectedOwnerKey, $belongsTo->getOwnerKeyName());
        $this->assertSame($expectedRelation, $belongsTo->getRelationName());
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    public static function userTestDataProvider(): array
    {
        return [
            [
                User::class,
                'user_id',
                'id',
                'users'
            ]
        ];
    }

    /**
     * @dataProvider userTestDataProvider
     */
    public function testUser(
        string $expectedRelated,
        string $expectedForeignKey,
        string $expectedOwnerKey,
        string $expectedRelation
    ): void {
        Facade::setFacadeApplication(app());

        app()->bind(
            'config',
            fn () => $this->createStub(Repository::class)
        );

        $this->connectionResolverMock->expects(self::once())
            ->method('connection')
            ->with('mysql')
            ->willReturn($this->connectionMock);

        $this->connectionMock->expects(self::once())
            ->method('query')
            ->wilLReturn($this->builderMock);

        $belongsTo = $this->getModel()->user();

        $this->assertSame($expectedRelated, $belongsTo->getRelated()::class);
        $this->assertSame($expectedForeignKey, $belongsTo->getForeignKeyName());
        $this->assertSame($expectedOwnerKey, $belongsTo->getOwnerKeyName());
        $this->assertSame($expectedRelation, $belongsTo->getRelationName());
    }
}
