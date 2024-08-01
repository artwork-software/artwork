<?php

namespace Tests\Unit\Artwork\Modules\InventoryManagement\Http\Controller;

use Artwork\Modules\InventoryManagement\Http\Controller\CraftInventoryItemCellController;
use Artwork\Modules\InventoryManagement\Http\Requests\ItemCell\UpdateCraftInventoryItemCellCellValueRequest;
use Artwork\Modules\InventoryManagement\Models\CraftInventoryItemCell;
use Artwork\Modules\InventoryManagement\Services\CraftInventoryItemCellService;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;
use Illuminate\Translation\Translator;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class CraftInventoryItemCellControllerTest extends TestCase
{
    private LoggerInterface $loggerMock;

    private Redirector $redirectorMock;

    private CraftInventoryItemCellService $craftInventoryItemCellServiceMock;

    private Translator $translatorMock;

    private function getController(): CraftInventoryItemCellController
    {
        return new CraftInventoryItemCellController(
            $this->loggerMock,
            $this->redirectorMock,
            $this->craftInventoryItemCellServiceMock,
            $this->translatorMock
        );
    }

    public function setUp(): void
    {
        $this->loggerMock = $this->getMockBuilder(NullLogger::class)
            ->onlyMethods(['error'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->redirectorMock = $this->getMockBuilder(Redirector::class)
            ->onlyMethods(['back'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->craftInventoryItemCellServiceMock = $this->getMockBuilder(CraftInventoryItemCellService::class)
            ->onlyMethods(['updateCellValue'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->translatorMock = $this->getMockBuilder(Translator::class)
            ->onlyMethods(['get'])
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testUpdateCellValueSuccess(): void
    {
        $expectedCellValueKey = 'cell_value';
        $expectedCellValue = 'Test';

        $craftInventoryItemCellMock = $this->getMockBuilder(CraftInventoryItemCell::class)
            ->disableOriginalConstructor()
            ->getMock();

        $requestMock = $this->getMockBuilder(UpdateCraftInventoryItemCellCellValueRequest::class)
            ->onlyMethods(['string'])
            ->disableOriginalConstructor()
            ->getMock();

        $redirectResponse = $this->getMockBuilder(RedirectResponse::class)
            ->disableOriginalConstructor()
            ->getMock();

        $requestMock->expects(self::once())
            ->method('string')
            ->with($expectedCellValueKey)
            ->willReturn($expectedCellValue);

        $this->redirectorMock->expects(self::once())
            ->method('back')
            ->willReturn($redirectResponse);

        $this->craftInventoryItemCellServiceMock->expects(self::once())
            ->method('updateCellValue')
            ->with($expectedCellValue, $craftInventoryItemCellMock);

        self::assertInstanceOf(
            RedirectResponse::class,
            $this->getController()->updateCellValue($craftInventoryItemCellMock, $requestMock)
        );
    }

    public function testUpdateCellValueException(): void
    {
        $expectedCellValueKey = 'cell_value';
        $expectedCellValue = 'Test';
        $expectedException = new Exception('Test');

        $craftInventoryItemCellMock = $this->getMockBuilder(CraftInventoryItemCell::class)
            ->disableOriginalConstructor()
            ->getMock();

        $requestMock = $this->getMockBuilder(UpdateCraftInventoryItemCellCellValueRequest::class)
            ->onlyMethods(['string'])
            ->disableOriginalConstructor()
            ->getMock();

        $redirectResponse = $this->getMockBuilder(RedirectResponse::class)
            ->disableOriginalConstructor()
            ->getMock();

        $requestMock->expects(self::once())
            ->method('string')
            ->with($expectedCellValueKey)
            ->willReturn($expectedCellValue);

        $this->redirectorMock->expects(self::once())
            ->method('back')
            ->willReturn($redirectResponse);

        $this->craftInventoryItemCellServiceMock->expects(self::once())
            ->method('updateCellValue')
            ->with($expectedCellValue, $craftInventoryItemCellMock)
            ->willThrowException($expectedException);

        $this->loggerMock->expects(self::once())
            ->method('error')
            ->with(
                sprintf(
                    'Could not update crafts inventory cell value to: "%s" for reason: "%s"',
                    $expectedCellValue,
                    'Test'
                )
            );

        $redirectResponse->expects(self::once())
            ->method('with')
            ->with('error', 'translation')
            ->willReturn($redirectResponse);

        $this->translatorMock->expects(self::once())
            ->method('get')
            ->with('flash-messages.inventory-management.item-cell.errors.updateCellValue')
            ->willReturn('translation');

        self::assertInstanceOf(
            RedirectResponse::class,
            $this->getController()->updateCellValue($craftInventoryItemCellMock, $requestMock)
        );
    }
}
