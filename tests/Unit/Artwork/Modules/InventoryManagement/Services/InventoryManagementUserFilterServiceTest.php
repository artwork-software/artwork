<?php

namespace Tests\Unit\Artwork\Modules\InventoryManagement\Services;

use Artwork\Modules\InventoryManagement\Models\InventoryManagementUserFilter;
use Artwork\Modules\InventoryManagement\Repositories\InventoryManagementUserFilterRepository;
use Artwork\Modules\InventoryManagement\Services\InventoryManagementUserFilterService;
use Exception;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;
use Throwable;

class InventoryManagementUserFilterServiceTest extends TestCase
{
    private readonly InventoryManagementUserFilterRepository $inventoryManagementUserFilterRepositoryMock;

    protected function setUp(): void
    {
        $this->inventoryManagementUserFilterRepositoryMock = $this
            ->getMockBuilder(InventoryManagementUserFilterRepository::class)
            ->onlyMethods([
                'findForUser',
                'updateOrFail',
                'saveOrFail',
                'getNewModelInstance'
            ])
            ->disableOriginalConstructor()
            ->getMock();
    }

    private function getService(): InventoryManagementUserFilterService
    {
        return new InventoryManagementUserFilterService($this->inventoryManagementUserFilterRepositoryMock);
    }

    public function testGetFilterOfUserSuccess(): void
    {
        $inventoryManagementUserFilterMock = $this->getMockBuilder(InventoryManagementUserFilter::class)
            ->onlyMethods(['getAttribute'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->inventoryManagementUserFilterRepositoryMock->expects(self::once())
            ->method('findForUser')
            ->with(1)
            ->willReturn($inventoryManagementUserFilterMock);

        $inventoryManagementUserFilterMock->expects(self::once())
            ->method('getAttribute')
            ->willReturn(['abc']);

        self::assertSame(
            ['abc'],
            $this->getService()->getFilterOfUser(1)
        );
    }

    public function testGetFilterOfUserIdNotFound(): void
    {
        $this->inventoryManagementUserFilterRepositoryMock->expects(self::once())
            ->method('findForUser')
            ->with(1)
            ->willReturn(null);

        self::assertSame(
            [],
            $this->getService()->getFilterOfUser(1)
        );
    }

    /**
     * @throws Throwable
     */
    public function testUpdateSuccess(): void
    {
        $expectedUserId = 1;
        $expectedCraftIds = Collection::make();

        $inventoryManagementUserFilterMock = $this->getMockBuilder(InventoryManagementUserFilter::class)
            ->onlyMethods(['getAttribute'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->inventoryManagementUserFilterRepositoryMock->expects(self::once())
            ->method('findForUser')
            ->with(1)
            ->willReturn($inventoryManagementUserFilterMock);

        $this->inventoryManagementUserFilterRepositoryMock->expects(self::once())
            ->method('updateOrFail')
            ->with(
                $inventoryManagementUserFilterMock,
                [
                    'user_id' => $expectedUserId,
                    'filter' => $expectedCraftIds
                ]
            )->willReturn($inventoryManagementUserFilterMock);

        $this->inventoryManagementUserFilterRepositoryMock
            ->expects(self::never())->method('saveOrFail');

        $this->inventoryManagementUserFilterRepositoryMock
            ->expects(self::never())->method('getNewModelInstance');

        $this->getService()->updateOrCreate(
            $expectedUserId,
            $expectedCraftIds
        );
    }

    /**
     * @throws Throwable
     */
    public function testUpdateFailsOnUpdateOrFail(): void
    {
        $expectedUserId = 1;
        $expectedCraftIds = Collection::make();

        $inventoryManagementUserFilterMock = $this->getMockBuilder(InventoryManagementUserFilter::class)
            ->onlyMethods(['getAttribute'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->inventoryManagementUserFilterRepositoryMock->expects(self::once())
            ->method('findForUser')
            ->with(1)
            ->willReturn($inventoryManagementUserFilterMock);

        $this->inventoryManagementUserFilterRepositoryMock->expects(self::once())
            ->method('updateOrFail')
            ->with(
                $inventoryManagementUserFilterMock,
                [
                    'user_id' => $expectedUserId,
                    'filter' => $expectedCraftIds
                ]
            )->willThrowException($expectedException = new Exception('Test'));

        $this->inventoryManagementUserFilterRepositoryMock
            ->expects(self::never())->method('saveOrFail');

        $this->inventoryManagementUserFilterRepositoryMock
            ->expects(self::never())->method('getNewModelInstance');

        try {
            $this->getService()->updateOrCreate(
                $expectedUserId,
                $expectedCraftIds
            );
        } catch (Throwable $t) {
            self::assertSame(
                $expectedException,
                $t
            );
        }
    }

    /**
     * @throws Throwable
     */
    public function testCreateSuccess(): void
    {
        $expectedUserId = 1;
        $expectedCraftIds = Collection::make();

        $inventoryManagementUserFilterMock = $this->getMockBuilder(InventoryManagementUserFilter::class)
            ->onlyMethods(['getAttribute'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->inventoryManagementUserFilterRepositoryMock->expects(self::once())
            ->method('findForUser')
            ->with(1)
            ->willReturn(null);

        $this->inventoryManagementUserFilterRepositoryMock->expects(self::once())
            ->method('getNewModelInstance')
            ->willReturn($inventoryManagementUserFilterMock);

        $this->inventoryManagementUserFilterRepositoryMock->expects(self::once())
            ->method('saveOrFail')
            ->with($inventoryManagementUserFilterMock)
            ->willReturn($inventoryManagementUserFilterMock);

        $this->getService()->updateOrCreate(
            $expectedUserId,
            $expectedCraftIds
        );
    }

    /**
     * @throws Throwable
     */
    public function testCreateFailsOnSaveOrFail(): void
    {
        $expectedUserId = 1;
        $expectedCraftIds = Collection::make();

        $inventoryManagementUserFilterMock = $this->getMockBuilder(InventoryManagementUserFilter::class)
            ->onlyMethods(['getAttribute'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->inventoryManagementUserFilterRepositoryMock->expects(self::once())
            ->method('findForUser')
            ->with(1)
            ->willReturn(null);

        $this->inventoryManagementUserFilterRepositoryMock->expects(self::once())
            ->method('getNewModelInstance')
            ->willReturn($inventoryManagementUserFilterMock);

        $this->inventoryManagementUserFilterRepositoryMock->expects(self::once())
            ->method('saveOrFail')
            ->with($inventoryManagementUserFilterMock)
            ->willThrowException($expectedException = new Exception('Test'));

        $this->inventoryManagementUserFilterRepositoryMock->expects(self::never())
            ->method('updateOrFail');

        try {
            $this->getService()->updateOrCreate(
                $expectedUserId,
                $expectedCraftIds
            );
        } catch (Throwable $t) {
            self::assertSame(
                $expectedException,
                $t
            );
        }
    }
}
