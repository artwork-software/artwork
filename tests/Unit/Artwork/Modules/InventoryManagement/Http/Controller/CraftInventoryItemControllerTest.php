<?php

namespace Tests\Unit\Artwork\Modules\InventoryManagement\Http\Controller;

use Artwork\Modules\InventoryManagement\Http\Controllers\CraftInventoryItemController;
use Artwork\Modules\InventoryManagement\Http\Requests\Item\CreateCraftInventoryItemRequest;
use Artwork\Modules\InventoryManagement\Http\Requests\Item\UpdateCraftInventoryItemOrderRequest;
use Artwork\Modules\InventoryManagement\Models\CraftInventoryItem;
use Artwork\Modules\InventoryManagement\Services\CraftInventoryItemService;
use AssertionError;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;
use Illuminate\Translation\Translator;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class CraftInventoryItemControllerTest extends TestCase
{
    private LoggerInterface $loggerMock;

    private Redirector $redirectorMock;

    private CraftInventoryItemService $craftInventoryItemServiceMock;

    private Translator $translatorMock;

    private function getController(): CraftInventoryItemController
    {
        return new CraftInventoryItemController(
            $this->loggerMock,
            $this->redirectorMock,
            $this->craftInventoryItemServiceMock,
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

        $this->craftInventoryItemServiceMock = $this->getMockBuilder(CraftInventoryItemService::class)
            ->onlyMethods(['create', 'updateOrder', 'forceDelete'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->translatorMock = $this->getMockBuilder(Translator::class)
            ->onlyMethods(['get'])
            ->disableOriginalConstructor()
            ->getMock();
    }

    /** @return array<string, array<int, mixed>> */
    public static function createTestDataProvider(): array
    {
        return [
            'test create' => [
                'order',
                0,
                'groupId',
                1,
                'folderId',
                null
            ]
        ];
    }

    /**
     * @dataProvider createTestDataProvider
     */
    public function testCreate(
        string $expectedOrderKey,
        int $expectedOrder,
        string $expectedGroupIdKey,
        int $expectedGroupId,
        string $expectedFolderIdKey,
        ?int $expectedFolderId
    ): void {
        $requestMock = $this->getMockBuilder(CreateCraftInventoryItemRequest::class)
            ->onlyMethods(['integer'])
            ->disableOriginalConstructor()
            ->getMock();

        $redirectResponseMock = $this->getMockBuilder(RedirectResponse::class)
            ->disableOriginalConstructor()
            ->getMock();

        $requestMock->expects($matcher = self::exactly(3))
            ->method('integer')
            ->willReturnCallback(
                function (string $key) use (
                    $matcher,
                    $expectedOrderKey,
                    $expectedOrder,
                    $expectedGroupIdKey,
                    $expectedGroupId,
                    $expectedFolderIdKey,
                    $expectedFolderId
                ): ?int {
                    switch ($matcher->numberOfInvocations()) {
                        case 1:
                            self::assertSame($expectedGroupIdKey, $key);
                            return $expectedGroupId;
                        case 2:
                            self::assertSame($expectedFolderIdKey, $key);
                            return $expectedFolderId;
                        case 3:
                            self::assertSame($expectedOrderKey, $key);
                            return $expectedOrder;
                        default:
                            throw new AssertionError('Number of invocations not expected.');
                    }
                }
            );

        $this->craftInventoryItemServiceMock->expects(self::once())
            ->method('create')
            ->with($expectedOrder, $expectedGroupId, $expectedFolderId);

        $this->redirectorMock->expects(self::once())
            ->method('back')
            ->willReturn($redirectResponseMock);

        self::assertInstanceOf(
            RedirectResponse::class,
            $this->getController()->create($requestMock)
        );
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function createExceptionTestDataProvider(): array
    {
        return [
            'test create exception' => [
                'order',
                0,
                'groupId',
                1,
                'folderId',
                null,
                'error',
                'flash-messages.inventory-management.item.errors.create',
                'translation',
                new Exception('Test')
            ]
        ];
    }

    /** @dataProvider createExceptionTestDataProvider */
    public function testCreateException(
        string $expectedOrderKey,
        int $expectedOrder,
        string $expectedGroupIdKey,
        int $expectedGroupId,
        string $expectedFolderIdKey,
        ?int $expectedFolderId,
        string $expectedWithKey,
        string $expectedTranslationKey,
        string $expectedTranslation,
        Exception $thrownException
    ): void {
        $redirectResponseMock = $this->getMockBuilder(RedirectResponse::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['with'])
            ->getMock();

        $requestMock = $this->getMockBuilder(CreateCraftInventoryItemRequest::class)
            ->onlyMethods(['integer'])
            ->disableOriginalConstructor()
            ->getMock();

        $requestMock->expects($matcher = self::exactly(3))
            ->method('integer')
            ->willReturnCallback(
                function (string $key) use (
                    $matcher,
                    $expectedOrderKey,
                    $expectedOrder,
                    $expectedGroupIdKey,
                    $expectedGroupId,
                    $expectedFolderIdKey,
                    $expectedFolderId
                ): ?int {
                    switch ($matcher->numberOfInvocations()) {
                        case 1:
                            self::assertSame($expectedGroupIdKey, $key);
                            return $expectedGroupId;
                        case 2:
                            self::assertSame($expectedFolderIdKey, $key);
                            return $expectedFolderId;
                        case 3:
                            self::assertSame($expectedOrderKey, $key);
                            return $expectedOrder;
                        default:
                            throw new AssertionError('Number of invocations not expected.');
                    }
                }
            );

        $this->craftInventoryItemServiceMock->expects(self::once())
            ->method('create')
            ->with($expectedOrder, $expectedGroupId, $expectedFolderId)
            ->willThrowException($thrownException);

        $this->loggerMock->expects(self::once())
            ->method('error')
            ->with('Could not create crafts inventory item for reason: "Test"');

        $this->translatorMock->expects(self::once())
            ->method('get')
            ->with($expectedTranslationKey)
            ->willReturn($expectedTranslation);

        $this->redirectorMock->expects(self::once())
            ->method('back')
            ->willReturn($redirectResponseMock);

        $redirectResponseMock->expects(self::once())
            ->method('with')
            ->with($expectedWithKey, $expectedTranslation)
            ->willReturn($redirectResponseMock);

        self::assertInstanceOf(
            RedirectResponse::class,
            $this->getController()->create($requestMock)
        );
    }

    /** @return array<string, array<int, mixed>> */
    public static function updateOrderTestDataProvider(): array
    {
        return [
            'test updateOrder' => [
                'order',
                0
            ]
        ];
    }

    /**
     * @dataProvider updateOrderTestDataProvider
     */
    public function testUpdateOrder(
        string $expectedOrderKey,
        int $expectedOrder
    ): void {
        $requestMock = $this->getMockBuilder(UpdateCraftInventoryItemOrderRequest::class)
            ->onlyMethods(['integer'])
            ->disableOriginalConstructor()
            ->getMock();

        $redirectResponseMock = $this->getMockBuilder(RedirectResponse::class)
            ->disableOriginalConstructor()
            ->getMock();

        $requestMock->expects(self::once())
            ->method('integer')
            ->with($expectedOrderKey)
            ->willReturn($expectedOrder);

        $craftInventoryItemMock = $this->getMockBuilder(CraftInventoryItem::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->craftInventoryItemServiceMock->expects(self::once())
            ->method('updateOrder')
            ->with($craftInventoryItemMock, $expectedOrder);

        $this->redirectorMock->expects(self::once())
            ->method('back')
            ->willReturn($redirectResponseMock);

        self::assertInstanceOf(
            RedirectResponse::class,
            $this->getController()->updateOrder($craftInventoryItemMock, $requestMock)
        );
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function updateOrderExceptionTestDataProvider(): array
    {
        return [
            'test updateOrder exception' => [
                'order',
                0,
                'error',
                'flash-messages.inventory-management.item.errors.updateOrder',
                'translation',
                new Exception('Test')
            ]
        ];
    }

    /** @dataProvider updateOrderExceptionTestDataProvider */
    public function testUpdateOrderException(
        string $expectedOrderKey,
        int $expectedOrder,
        string $expectedWithKey,
        string $expectedTranslationKey,
        string $expectedTranslation,
        Exception $thrownException
    ): void {
        $redirectResponseMock = $this->getMockBuilder(RedirectResponse::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['with'])
            ->getMock();

        $requestMock = $this->getMockBuilder(UpdateCraftInventoryItemOrderRequest::class)
            ->onlyMethods(['integer'])
            ->disableOriginalConstructor()
            ->getMock();

        $requestMock->expects(self::once())
            ->method('integer')
            ->with($expectedOrderKey)
            ->willReturn($expectedOrder);

        $craftInventoryItemMock = $this->getMockBuilder(CraftInventoryItem::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->craftInventoryItemServiceMock->expects(self::once())
            ->method('updateOrder')
            ->with($craftInventoryItemMock, $expectedOrder)
            ->willThrowException($thrownException);

        $this->loggerMock->expects(self::once())
            ->method('error')
            ->with(sprintf(
                'Could not update crafts inventory item order to: "%s" for reason: "%s"',
                $expectedOrder,
                'Test'
            ));

        $this->translatorMock->expects(self::once())
            ->method('get')
            ->with($expectedTranslationKey)
            ->willReturn($expectedTranslation);

        $this->redirectorMock->expects(self::once())
            ->method('back')
            ->willReturn($redirectResponseMock);

        $redirectResponseMock->expects(self::once())
            ->method('with')
            ->with($expectedWithKey, $expectedTranslation)
            ->willReturn($redirectResponseMock);

        self::assertInstanceOf(
            RedirectResponse::class,
            $this->getController()->updateOrder($craftInventoryItemMock, $requestMock)
        );
    }

    public function testForceDeleteSuccess(): void
    {
        $redirectResponseMock = $this->getMockBuilder(RedirectResponse::class)
            ->disableOriginalConstructor()
            ->getMock();

        $craftInventoryItemMock = $this->getMockBuilder(CraftInventoryItem::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->craftInventoryItemServiceMock->expects(self::once())
            ->method('forceDelete')
            ->with($craftInventoryItemMock)
            ->willReturn(true);

        $this->redirectorMock->expects(self::once())
            ->method('back')
            ->willReturn($redirectResponseMock);

        self::assertInstanceOf(
            RedirectResponse::class,
            $this->getController()->forceDelete($craftInventoryItemMock)
        );
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function forceDeleteFailureTestDataProvider(): array
    {
        return [
            'test force delete failure' => [
                'error',
                'flash-messages.inventory-management.item.errors.delete',
                'translation'
            ]
        ];
    }

    /** @dataProvider forceDeleteFailureTestDataProvider */
    public function testForceDeleteFailure(
        string $expectedWithKey,
        string $expectedTranslationKey,
        string $expectedTranslation
    ): void {
        $redirectResponseMock = $this->getMockBuilder(RedirectResponse::class)
            ->onlyMethods(['with'])
            ->disableOriginalConstructor()
            ->getMock();

        $craftInventoryItemMock = $this->getMockBuilder(CraftInventoryItem::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->craftInventoryItemServiceMock->expects(self::once())
            ->method('forceDelete')
            ->with($craftInventoryItemMock)
            ->willReturn(false);

        $this->redirectorMock->expects(self::once())
            ->method('back')
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
            $this->getController()->forceDelete($craftInventoryItemMock)
        );
    }
}
