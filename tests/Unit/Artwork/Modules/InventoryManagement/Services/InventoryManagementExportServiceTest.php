<?php

namespace Tests\Unit\Artwork\Modules\InventoryManagement\Services;

use Artwork\Modules\Inventory\Exports\InventoryManagementExport;
use Artwork\Modules\InventoryManagement\Services\CraftsInventoryColumnService;
use Artwork\Modules\InventoryManagement\Services\InventoryManagementExportService;
use AssertionError;
use Carbon\Carbon;
use Exception;
use Illuminate\Cache\CacheManager;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use DragonCode\Support\Helpers\Str;
use PHPUnit\Framework\TestCase;
use Psr\SimpleCache\InvalidArgumentException;
use Throwable;

class InventoryManagementExportServiceTest extends TestCase
{
    private readonly CacheManager $cacheManagerMock;

    private readonly CraftsInventoryColumnService $craftsInventoryColumnServiceMock;

    private readonly InventoryManagementExport $inventoryManagementExportMock;

    private readonly Str $strMock;

    protected function setUp(): void
    {
        $this->cacheManagerMock = $this->getMockBuilder(CacheManager::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['__call'])
            ->getMock();

        $this->craftsInventoryColumnServiceMock = $this->getMockBuilder(CraftsInventoryColumnService::class)
            ->onlyMethods(['getAllOrdered'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->inventoryManagementExportMock = $this->getMockBuilder(InventoryManagementExport::class)
            ->onlyMethods(['setColumns', 'setCrafts'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->strMock = $this->getMockBuilder(Str::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['random'])
            ->getMock();
    }

    public function getService(): InventoryManagementExportService
    {
        return new InventoryManagementExportService(
            $this->cacheManagerMock,
            $this->craftsInventoryColumnServiceMock,
            $this->inventoryManagementExportMock,
            $this->strMock
        );
    }

    /**
     * @throws InvalidArgumentException
     */
    public function testCacheRequestDataSuccess(): void
    {
        $expectedCollection = SupportCollection::make();
        $expectedCacheKey = 'cache-key';

        $this->strMock->expects(self::once())
            ->method('random')
            ->willReturn($expectedCacheKey);

        $this->cacheManagerMock->expects(self::once())
            ->method('__call')
            ->with('set', [$expectedCacheKey, $expectedCollection, 10])
            ->willReturn(true);

        self::assertSame(
            $expectedCacheKey,
            $this->getService()->cacheRequestData($expectedCollection)
        );
    }

    public function testCacheRequestDataExceptionOnSet(): void
    {
        $expectedCollection = SupportCollection::make();
        $expectedCacheKey = 'cache-key';

        $this->strMock->expects(self::once())
            ->method('random')
            ->willReturn($expectedCacheKey);

        $this->cacheManagerMock->expects(self::once())
            ->method('__call')
            ->with('set', [$expectedCacheKey, $expectedCollection, 10])
            ->willThrowException($expectedException = new Exception('Test'));

        try {
            $this->getService()->cacheRequestData($expectedCollection);
        } catch (Throwable $t) {
            self::assertSame(
                $expectedException,
                $t
            );
        }
    }

    /**
     * @throws InvalidArgumentException
     */
    public function testGetCachedRequestDataSuccess(): void
    {
        $expectedCacheKey = 'cache-key';
        $expectedCollection = SupportCollection::make();

        $this->cacheManagerMock->expects($matcher = self::exactly(2))
            ->method('__call')
            ->willReturnCallback(
                function (
                    string $function,
                    array $params
                ) use (
                    $matcher,
                    $expectedCacheKey,
                    $expectedCollection
                ): SupportCollection|bool {
                    switch ($matcher->numberOfInvocations()) {
                        case 1:
                            self::assertSame($function, 'get');
                            self::assertSame($params[0], $expectedCacheKey);
                            return $expectedCollection;
                        case 2:
                            self::assertSame($function, 'delete');
                            self::assertSame($params[0], $expectedCacheKey);
                            return true;
                        default:
                            throw new AssertionError('Number of invocations not expected.');
                    }
                }
            );

        self::assertSame(
            $expectedCollection,
            $this->getService()->getCachedRequestData($expectedCacheKey)
        );
    }

    public function testGetCachedRequestDataFailsOnGet(): void
    {
        $expectedCacheKey = 'cache-key';

        $this->cacheManagerMock->expects(self::once())
            ->method('__call')
            ->with('get', [$expectedCacheKey])
            ->willThrowException($expectedException = new Exception('Test'));

        try {
            $this->getService()->getCachedRequestData($expectedCacheKey);
        } catch (Throwable $t) {
            self::assertSame(
                $expectedException,
                $t
            );
        }
    }

    /**
     * @throws InvalidArgumentException
     */
    public function testGetConfiguredExportSuccess(): void
    {
        $expectedCacheKey = 'cache-key';
        $expectedSupportCollection = SupportCollection::make();
        $expectedEloquentCollection = Collection::make();

        $this->craftsInventoryColumnServiceMock->expects(self::once())
            ->method('getAllOrdered')
            ->willReturn($expectedEloquentCollection);

        $this->inventoryManagementExportMock->expects(self::once())
            ->method('setColumns')
            ->with($expectedEloquentCollection)
            ->willReturnSelf();

        $this->cacheManagerMock->expects($matcher = self::exactly(2))
            ->method('__call')
            ->willReturnCallback(
                function (
                    string $function,
                    array $params
                ) use (
                    $matcher,
                    $expectedCacheKey,
                    $expectedSupportCollection
                ): SupportCollection|bool {
                    switch ($matcher->numberOfInvocations()) {
                        case 1:
                            self::assertSame($function, 'get');
                            self::assertSame($params[0], $expectedCacheKey);
                            return $expectedSupportCollection;
                        case 2:
                            self::assertSame($function, 'delete');
                            self::assertSame($params[0], $expectedCacheKey);
                            return true;
                        default:
                            throw new AssertionError('Number of invocations not expected.');
                    }
                }
            );

        $this->inventoryManagementExportMock->expects(self::once())
            ->method('setCrafts')
            ->with($expectedSupportCollection)
            ->willReturnSelf();

        $this->getService()->getConfiguredExport($expectedCacheKey);
    }

    public function testCreateXlsxOrPdfExportFilename(): void
    {
        self::assertSame(
            sprintf(
                'artwork_inventory_management_%s.xlsx',
                Carbon::now()->format('d-m-Y_H_i_s')
            ),
            $this->getService()->createXlsxExportFilename()
        );

        self::assertSame(
            sprintf(
                'artwork_inventory_management_%s.pdf',
                Carbon::now()->format('d-m-Y_H_i_s')
            ),
            $this->getService()->createPdfExportFilename()
        );
    }
}
