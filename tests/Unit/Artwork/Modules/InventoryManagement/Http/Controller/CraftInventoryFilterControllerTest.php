<?php

namespace Tests\Unit\Artwork\Modules\InventoryManagement\Http\Controller;

use Artwork\Modules\InventoryManagement\Http\Controller\CraftInventoryFilterController;
use Artwork\Modules\InventoryManagement\Http\Requests\Filter\UpdateOrCreateInventoryFilterRequest;
use Artwork\Modules\InventoryManagement\Services\InventoryManagementUserFilterService;
use Exception;
use Illuminate\Auth\AuthManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Collection;
use Illuminate\Translation\Translator;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class CraftInventoryFilterControllerTest extends TestCase
{
    private LoggerInterface $loggerMock;
    private Redirector $redirectorMock;
    private InventoryManagementUserFilterService $inventoryManagementUserFilterServiceMock;
    private Translator $translatorMock;

    protected function setUp(): void
    {
        $this->translatorMock = $this->getMockBuilder(Translator::class)
            ->onlyMethods(['get'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->loggerMock = $this->getMockBuilder(NullLogger::class)
            ->onlyMethods(['error'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->redirectorMock = $this->getMockBuilder(Redirector::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['back'])
            ->getMock();

        $this->inventoryManagementUserFilterServiceMock = $this
            ->getMockBuilder(InventoryManagementUserFilterService::class)
            ->onlyMethods(['updateOrCreate'])
            ->disableOriginalConstructor()
            ->getMock();
    }

    private function getController(): CraftInventoryFilterController
    {
        return new CraftInventoryFilterController(
            $this->loggerMock,
            $this->redirectorMock,
            $this->inventoryManagementUserFilterServiceMock,
            $this->translatorMock
        );
    }

    public function testUpdateOrCreateSuccess(): void
    {
        $expectedIdReturn = 1;

        $updateOrCreateInventoryFilterRequestMock = $this
            ->getMockBuilder(UpdateOrCreateInventoryFilterRequest::class)
            ->onlyMethods(['collect'])
            ->disableOriginalConstructor()
            ->getMock();

        $authManagerMock = $this->getMockBuilder(AuthManager::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['__call'])
            ->getMock();

        $collectionMock = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['map'])
            ->getMock();

        $redirectResponseMock = $this->getMockBuilder(RedirectResponse::class)
            ->disableOriginalConstructor()
            ->getMock();

        $updateOrCreateInventoryFilterRequestMock->expects(self::once())
            ->method('collect')
            ->with('filter')
            ->willReturn($collectionMock);

        $collectionMock->expects(self::once())
            ->method('map')
            ->with(fn(array $filter) => $filter['craftId'])
            ->willReturn($collectionMock);

        $this->inventoryManagementUserFilterServiceMock->expects(self::once())
            ->method('updateOrCreate')
            ->with($expectedIdReturn, $collectionMock);

        $authManagerMock->expects(self::once())
            ->method('__call')
            ->with('id')
            ->willReturn($expectedIdReturn);

        $this->redirectorMock->expects(self::once())
            ->method('back')
            ->willReturn($redirectResponseMock);

        self::assertInstanceOf(
            RedirectResponse::class,
            $this->getController()->updateOrCreate($updateOrCreateInventoryFilterRequestMock, $authManagerMock)
        );
    }

    public function testUpdateOrCreateException(): void
    {
        $expectedIdReturn = 1;
        $expectedException = new Exception('Test');

        $updateOrCreateInventoryFilterRequestMock = $this
            ->getMockBuilder(UpdateOrCreateInventoryFilterRequest::class)
            ->onlyMethods(['collect'])
            ->disableOriginalConstructor()
            ->getMock();

        $authManagerMock = $this->getMockBuilder(AuthManager::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['__call'])
            ->getMock();

        $collectionMock = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['map'])
            ->getMock();

        $redirectResponseMock = $this->getMockBuilder(RedirectResponse::class)
            ->disableOriginalConstructor()
            ->getMock();

        $updateOrCreateInventoryFilterRequestMock->expects(self::once())
            ->method('collect')
            ->with('filter')
            ->willReturn($collectionMock);

        $collectionMock->expects(self::once())
            ->method('map')
            ->with(fn(array $filter) => $filter['craftId'])
            ->willReturn($collectionMock);

        $authManagerMock->expects(self::once())
            ->method('__call')
            ->with('id')
            ->willReturn($expectedIdReturn);

        $this->inventoryManagementUserFilterServiceMock->expects(self::once())
            ->method('updateOrCreate')
            ->willThrowException($expectedException);

        $this->loggerMock->expects(self::once())
            ->method('error')
            ->with('Could not update inventory management user filter to: "" for reason: "Test"');

        $this->translatorMock->expects(self::once())
            ->method('get')
            ->with('flash-messages.inventory-management.filter.errors.updateOrCreate')
            ->willReturn('Test');

        $this->redirectorMock->expects(self::once())
            ->method('back')
            ->willReturn($redirectResponseMock);

        $redirectResponseMock->expects(self::once())
            ->method('with')
            ->with('error', 'Test')
            ->willReturn($redirectResponseMock);

        self::assertInstanceOf(
            RedirectResponse::class,
            $this->getController()->updateOrCreate($updateOrCreateInventoryFilterRequestMock, $authManagerMock)
        );
    }
}
