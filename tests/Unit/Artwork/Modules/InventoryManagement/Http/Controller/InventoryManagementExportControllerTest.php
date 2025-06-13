<?php

namespace Tests\Unit\Artwork\Modules\InventoryManagement\Http\Controller;

use Artwork\Modules\Inventory\Exports\InventoryManagementExport;
use Artwork\Modules\InventoryManagement\Http\Controllers\InventoryManagementExportController;
use Artwork\Modules\InventoryManagement\Http\Requests\Export\CreateInventoryManagementExportRequest;
use Artwork\Modules\InventoryManagement\Services\InventoryManagementExportService;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Collection;
use Illuminate\Translation\Translator;
use Maatwebsite\Excel\Excel;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class InventoryManagementExportControllerTest extends TestCase
{
    private readonly InventoryManagementExportService $inventoryManagementExportServiceMock;

    private readonly Redirector $redirectorMock;

    private readonly LoggerInterface $loggerMock;

    private readonly Translator $translatorMock;

    protected function setUp(): void
    {
        $this->inventoryManagementExportServiceMock = $this->getMockBuilder(InventoryManagementExportService::class)
            ->onlyMethods(
                [
                    'cacheRequestData',
                    'getConfiguredExport',
                    'createXlsxExportFilename',
                    'createPdfExportFilename',
                    'getCachedRequestData',
                ]
            )
            ->disableOriginalConstructor()
            ->getMock();

        $this->redirectorMock = $this->getMockBuilder(Redirector::class)
            ->onlyMethods(['route'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->loggerMock = $this->getMockBuilder(NullLogger::class)
            ->onlyMethods(['error'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->translatorMock = $this->getMockBuilder(Translator::class)
            ->onlyMethods(['get'])
            ->disableOriginalConstructor()
            ->getMock();
    }

    private function getController(): InventoryManagementExportController
    {
        return new InventoryManagementExportController(
            $this->inventoryManagementExportServiceMock,
            $this->redirectorMock,
            $this->loggerMock,
            $this->translatorMock
        );
    }

    /**
     * @throws Throwable
     */
    public function testSaveExportDataInCacheSuccess(): void
    {
        $expectedCollection = Collection::make();
        $requestMock = $this->getMockBuilder(CreateInventoryManagementExportRequest::class)
            ->onlyMethods(['collect'])
            ->disableOriginalConstructor()
            ->getMock();

        $requestMock->expects(self::once())
            ->method('collect')
            ->with('data')
            ->willReturn($expectedCollection);

        $this->inventoryManagementExportServiceMock->expects(self::once())
            ->method('cacheRequestData')
            ->with($expectedCollection)
            ->willReturn('cache-key-deluxe');

        $response = $this->getController()->saveExportDataInCache($requestMock);

        self::assertSame('cache-key-deluxe', $response);
    }

    public function testSaveExportDataInCacheError(): void
    {
        $expectedCollection = Collection::make();
        $expectedException = new Exception('No cache-key-deluxe today');

        $requestMock = $this->getMockBuilder(CreateInventoryManagementExportRequest::class)
            ->onlyMethods(['collect'])
            ->disableOriginalConstructor()
            ->getMock();

        $requestMock->expects(self::once())
            ->method('collect')
            ->with('data')
            ->willReturn($expectedCollection);

        //make sure the exception is handled with proper logging...
        $this->inventoryManagementExportServiceMock->expects(self::once())
            ->method('cacheRequestData')
            ->with($expectedCollection)
            ->willThrowException($expectedException);

        $this->loggerMock->expects(self::once())
            ->method('error')
            ->with('Could not cache export data for reason "No cache-key-deluxe today"');

        //...before it is thrown to outer space where the framework magically creates a
        //json response from given throwable object
        //(and we assert the throwable object has not changed on its way out of the controller)
        try {
            $this->getController()->saveExportDataInCache($requestMock);
        } catch (Throwable $t) {
            self::assertSame($expectedException, $t);
        }
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function downloadXlsxSuccessTestDataProvider(): array
    {
        return [
            'test download xlsx success' => [
                'cache-key-deluxe',
                'Test'
            ]
        ];
    }

    /**
     * @dataProvider downloadXlsxSuccessTestDataProvider
     */
    public function testDownloadXlsxSuccess(
        string $expectedCacheToken,
        string $expectedExportName
    ): void {
        $inventoryExportMock = $this->getMockBuilder(InventoryManagementExport::class)
            ->onlyMethods(['download'])
            ->disableOriginalConstructor()
            ->getMock();

        $binaryFileResponseMock = $this->getMockBuilder(BinaryFileResponse::class)
            ->onlyMethods(['deleteFileAfterSend'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->inventoryManagementExportServiceMock->expects(self::once())
            ->method('getConfiguredExport')
            ->with($expectedCacheToken)
            ->willReturn($inventoryExportMock);

        $this->inventoryManagementExportServiceMock->expects(self::once())
            ->method('createXlsxExportFilename')
            ->willReturn($expectedExportName);

        $inventoryExportMock->expects(self::once())
            ->method('download')
            ->with($expectedExportName)
            ->willReturn($binaryFileResponseMock);

        $binaryFileResponseMock->expects(self::once())
            ->method('deleteFileAfterSend')
            ->willReturn($binaryFileResponseMock);

        self::assertInstanceOf(
            BinaryFileResponse::class,
            $this->getController()->downloadXlsx($expectedCacheToken)
        );
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function downloadXlsxExceptionTestDataProvider(): array
    {
        return [
            'test download xlsx exception' => [
                'cache-key-deluxe',
                'Test',
                'inventory-management.inventory',
                'error',
                'flash-messages.inventory-management.export.errors.download',
                'translation'
            ]
        ];
    }

    /**
     * @dataProvider downloadXlsxExceptionTestDataProvider
     */
    public function testDownloadXlsxException(
        string $expectedCacheToken,
        string $expectedExportName,
        string $expectedRoute,
        string $expectedWithKey,
        string $expectedTranslationKey,
        string $expectedTranslation
    ): void {
        $inventoryExportMock = $this->getMockBuilder(InventoryManagementExport::class)
            ->onlyMethods(['download'])
            ->disableOriginalConstructor()
            ->getMock();

        $binaryFileResponseMock = $this->getMockBuilder(BinaryFileResponse::class)
            ->disableOriginalConstructor()
            ->getMock();

        $redirectResponseMock = $this->getMockBuilder(RedirectResponse::class)
            ->onlyMethods(['with'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->inventoryManagementExportServiceMock->expects(self::once())
            ->method('getConfiguredExport')
            ->with($expectedCacheToken)
            ->willReturn($inventoryExportMock);

        $this->inventoryManagementExportServiceMock->expects(self::once())
            ->method('createXlsxExportFilename')
            ->willReturn($expectedExportName);

        $inventoryExportMock->expects(self::once())
            ->method('download')
            ->with($expectedExportName)
            ->willReturn($binaryFileResponseMock);

        $binaryFileResponseMock->expects(self::once())
            ->method('deleteFileAfterSend')
            ->willThrowException(new Exception('Test'));

        $this->loggerMock->expects(self::once())
            ->method('error')
            ->with(sprintf(
                'Could not create xlsx export for reason "%s"',
                'Test'
            ));

        $this->redirectorMock->expects(self::once())
            ->method('route')
            ->with($expectedRoute)
            ->willReturn($redirectResponseMock);

        $this->translatorMock->expects(self::once())
            ->method('get')
            ->with($expectedTranslationKey)
            ->willReturn($expectedTranslation);

        $redirectResponseMock->expects(self::once())
            ->method('with')
            ->with($expectedWithKey, $expectedTranslation)
            ->willReturn($redirectResponseMock);

        self::assertInstanceOf(
            RedirectResponse::class,
            $this->getController()->downloadXlsx($expectedCacheToken)
        );
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function downloadPdfSuccessTestDataProvider(): array
    {
        return [
            'test download pdf success' => [
                'cache-key-deluxe',
                'Test'
            ]
        ];
    }

    /**
     * @dataProvider downloadPdfSuccessTestDataProvider
     */
    public function testDownloadPdfSuccess(
        string $expectedCacheToken,
        string $expectedExportName
    ): void {
        $inventoryExportMock = $this->getMockBuilder(InventoryManagementExport::class)
            ->onlyMethods(['download'])
            ->disableOriginalConstructor()
            ->getMock();

        $binaryFileResponseMock = $this->getMockBuilder(BinaryFileResponse::class)
            ->onlyMethods(['deleteFileAfterSend'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->inventoryManagementExportServiceMock->expects(self::once())
            ->method('getConfiguredExport')
            ->with($expectedCacheToken)
            ->willReturn($inventoryExportMock);

        $this->inventoryManagementExportServiceMock->expects(self::once())
            ->method('createPdfExportFilename')
            ->willReturn($expectedExportName);

        $inventoryExportMock->expects(self::once())
            ->method('download')
            ->with($expectedExportName, Excel::DOMPDF)
            ->willReturn($binaryFileResponseMock);

        $binaryFileResponseMock->expects(self::once())
            ->method('deleteFileAfterSend')
            ->willReturn($binaryFileResponseMock);

        self::assertInstanceOf(
            BinaryFileResponse::class,
            $this->getController()->downloadPdf($expectedCacheToken)
        );
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function downloadPdfExceptionTestDataProvider(): array
    {
        return [
            'test download pdf exception' => [
                'cache-key-deluxe',
                'Test',
                'inventory-management.inventory',
                'error',
                'flash-messages.inventory-management.export.errors.download',
                'translation'
            ]
        ];
    }

    /**
     * @dataProvider downloadPdfExceptionTestDataProvider
     */
    public function testDownloadPdfException(
        string $expectedCacheToken,
        string $expectedExportName,
        string $expectedRoute,
        string $expectedWithKey,
        string $expectedTranslationKey,
        string $expectedTranslation
    ): void {
        $inventoryExportMock = $this->getMockBuilder(InventoryManagementExport::class)
            ->onlyMethods(['download'])
            ->disableOriginalConstructor()
            ->getMock();

        $binaryFileResponseMock = $this->getMockBuilder(BinaryFileResponse::class)
            ->disableOriginalConstructor()
            ->getMock();

        $redirectResponseMock = $this->getMockBuilder(RedirectResponse::class)
            ->onlyMethods(['with'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->inventoryManagementExportServiceMock->expects(self::once())
            ->method('getConfiguredExport')
            ->with($expectedCacheToken)
            ->willReturn($inventoryExportMock);

        $this->inventoryManagementExportServiceMock->expects(self::once())
            ->method('createPdfExportFilename')
            ->willReturn($expectedExportName);

        $inventoryExportMock->expects(self::once())
            ->method('download')
            ->with($expectedExportName, Excel::DOMPDF)
            ->willReturn($binaryFileResponseMock);

        $binaryFileResponseMock->expects(self::once())
            ->method('deleteFileAfterSend')
            ->willThrowException(new Exception('Test'));

        $this->loggerMock->expects(self::once())
            ->method('error')
            ->with(sprintf(
                'Could not create pdf export for reason "%s"',
                'Test'
            ));

        $this->redirectorMock->expects(self::once())
            ->method('route')
            ->with($expectedRoute)
            ->willReturn($redirectResponseMock);

        $this->translatorMock->expects(self::once())
            ->method('get')
            ->with($expectedTranslationKey)
            ->willReturn($expectedTranslation);

        $redirectResponseMock->expects(self::once())
            ->method('with')
            ->with($expectedWithKey, $expectedTranslation)
            ->willReturn($redirectResponseMock);

        self::assertInstanceOf(
            RedirectResponse::class,
            $this->getController()->downloadPdf($expectedCacheToken)
        );
    }
}
