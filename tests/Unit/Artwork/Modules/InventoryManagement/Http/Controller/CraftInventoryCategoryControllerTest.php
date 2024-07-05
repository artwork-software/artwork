<?php

namespace Tests\Unit\Artwork\Modules\InventoryManagement\Http\Controller;

use Artwork\Modules\InventoryManagement\Http\Controller\CraftInventoryCategoryController;
use Artwork\Modules\InventoryManagement\Http\Requests\Category\CreateCraftInventoryCategoryRequest;
use Artwork\Modules\InventoryManagement\Http\Requests\Category\UpdateCraftInventoryCategoryNameRequest;
use Artwork\Modules\InventoryManagement\Http\Requests\Category\UpdateCraftInventoryCategoryOrderRequest;
use Artwork\Modules\InventoryManagement\Models\CraftInventoryCategory;
use Artwork\Modules\InventoryManagement\Services\CraftInventoryCategoryService;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;
use Illuminate\Translation\Translator;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class CraftInventoryCategoryControllerTest extends TestCase
{
    private Translator $translatorMock;

    private LoggerInterface $loggerMock;

    private Redirector $redirectorMock;

    private CraftInventoryCategoryService $craftInventoryCategoryServiceMock;

    public function setUp(): void
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

        $this->craftInventoryCategoryServiceMock = $this->getMockBuilder(CraftInventoryCategoryService::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create', 'updateName', 'updateOrder', 'forceDelete'])
            ->getMock();
    }

    private function getController(): CraftInventoryCategoryController
    {
        return new CraftInventoryCategoryController(
            $this->translatorMock,
            $this->loggerMock,
            $this->redirectorMock,
            $this->craftInventoryCategoryServiceMock
        );
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function createTestSuccessDataProvider(): array
    {
        return [
            'test create success' => [
                'craftId',
                1,
                'name',
                'Test',
            ]
        ];
    }

    /** @dataProvider createTestSuccessDataProvider */
    public function testCreateSuccess(
        string $expectedIntegerKey,
        int $expectedIntegerResult,
        string $expectedStringKey,
        string $expectedStringResult
    ): void {
        $redirectResponseMock = $this->getMockBuilder(RedirectResponse::class)
            ->disableOriginalConstructor()
            ->getMock();

        $craftInventoryCategoryMock = $this->getMockBuilder(CraftInventoryCategory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $requestMock = $this->getMockBuilder(CreateCraftInventoryCategoryRequest::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['integer', 'string'])
            ->getMock();

        $requestMock->expects(self::once())
            ->method('integer')
            ->with($expectedIntegerKey)
            ->willReturn($expectedIntegerResult);

        $requestMock->expects(self::once())
            ->method('string')
            ->with($expectedStringKey)
            ->willReturn($expectedStringResult);

        $this->craftInventoryCategoryServiceMock->expects(self::once())
            ->method('create')
            ->with($expectedIntegerResult, $expectedStringResult)
            ->willReturn($craftInventoryCategoryMock);

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
    public static function createTestExceptionDataProvider(): array
    {
        return [
            'test create exception' => [
                'craftId',
                1,
                'name',
                'Test',
                'error',
                'flash-messages.inventory-management.category.errors.create',
                'translation',
                new Exception('Test')
            ]
        ];
    }

    /** @dataProvider createTestExceptionDataProvider */
    public function testCreateException(
        string $expectedIntegerKey,
        int $expectedIntegerResult,
        string $expectedStringKey,
        string $expectedStringResult,
        string $expectedWithKey,
        string $expectedTranslationKey,
        string $expectedTranslation,
        Exception $thrownException
    ): void {
        $this->craftInventoryCategoryServiceMock->expects(self::once())
            ->method('create')
            ->with($expectedIntegerResult, $expectedStringResult)
            ->willThrowException($thrownException);

        $this->loggerMock = $this->getMockBuilder(NullLogger::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['error'])
            ->getMock();

        $redirectResponseMock = $this->getMockBuilder(RedirectResponse::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['with'])
            ->getMock();

        $requestMock = $this->getMockBuilder(CreateCraftInventoryCategoryRequest::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['integer', 'string'])
            ->getMock();

        $requestMock->expects(self::once())
            ->method('integer')
            ->with($expectedIntegerKey)
            ->willReturn($expectedIntegerResult);

        $requestMock->expects(self::once())
            ->method('string')
            ->with($expectedStringKey)
            ->willReturn($expectedStringResult);

        $this->loggerMock->expects(self::once())
            ->method('error')
            ->with('Could not create crafts inventory category for reason: "Test"');

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

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function updateNameTestSuccessDataProvider(): array
    {
        return [
            'test updateName success' => [
                'name',
                'Test',
            ]
        ];
    }

    /** @dataProvider updateNameTestSuccessDataProvider */
    public function testUpdateNameSuccess(
        string $expectedStringKey,
        string $expectedStringResult
    ): void {
        $requestMock = $this->getMockBuilder(UpdateCraftInventoryCategoryNameRequest::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['string'])
            ->getMock();

        $redirectResponseMock = $this->getMockBuilder(RedirectResponse::class)
            ->disableOriginalConstructor()
            ->getMock();

        $craftInventoryCategoryMock = $this->getMockBuilder(CraftInventoryCategory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $requestMock->expects(self::once())
            ->method('string')
            ->with($expectedStringKey)
            ->willReturn($expectedStringResult);

        $this->craftInventoryCategoryServiceMock->expects(self::once())
            ->method('updateName')
            ->with($expectedStringResult, $craftInventoryCategoryMock);

        $this->redirectorMock->expects(self::once())
            ->method('back')
            ->willReturn($redirectResponseMock);

        self::assertInstanceOf(
            RedirectResponse::class,
            $this->getController()->updateName($craftInventoryCategoryMock, $requestMock)
        );
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function updateNameTestExceptionDataProvider(): array
    {
        return [
            'test updateName exception' => [
                'name',
                'Test',
                'error',
                'flash-messages.inventory-management.category.errors.updateName',
                'translation',
                new Exception('Test')
            ]
        ];
    }

    /** @dataProvider updateNameTestExceptionDataProvider */
    public function testUpdateNameException(
        string $expectedStringKey,
        string $expectedStringResult,
        string $expectedWithKey,
        string $expectedTranslationKey,
        string $expectedTranslation,
        Exception $thrownException
    ): void {
        $redirectResponseMock = $this->getMockBuilder(RedirectResponse::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['with'])
            ->getMock();

        $requestMock = $this->getMockBuilder(UpdateCraftInventoryCategoryNameRequest::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['string'])
            ->getMock();

        $craftInventoryCategoryMock = $this->getMockBuilder(CraftInventoryCategory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $requestMock->expects(self::once())
            ->method('string')
            ->with($expectedStringKey)
            ->willReturn($expectedStringResult);

        $this->craftInventoryCategoryServiceMock->expects(self::once())
            ->method('updateName')
            ->with($expectedStringResult, $craftInventoryCategoryMock)
            ->willThrowException($thrownException);

        $this->loggerMock->expects(self::once())
            ->method('error')
            ->with(sprintf(
                'Could not update crafts inventory category name to: "%s" for reason: "%s"',
                $expectedStringResult,
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
            $this->getController()->updateName($craftInventoryCategoryMock, $requestMock)
        );
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function updateOrderTestSuccessDataProvider(): array
    {
        return [
            'test updateOrder success' => [
                'order',
                0,
            ]
        ];
    }

    /** @dataProvider updateOrderTestSuccessDataProvider */
    public function testUpdateOrderSuccess(
        string $expectedIntegerKey,
        int $expectedIntegerResult
    ): void {
        $requestMock = $this->getMockBuilder(UpdateCraftInventoryCategoryOrderRequest::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['integer'])
            ->getMock();

        $redirectResponseMock = $this->getMockBuilder(RedirectResponse::class)
            ->disableOriginalConstructor()
            ->getMock();

        $craftInventoryCategoryMock = $this->getMockBuilder(CraftInventoryCategory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $requestMock->expects(self::once())
            ->method('integer')
            ->with($expectedIntegerKey)
            ->willReturn($expectedIntegerResult);

        $this->craftInventoryCategoryServiceMock->expects(self::once())
            ->method('updateOrder')
            ->with($craftInventoryCategoryMock, $expectedIntegerResult);

        $this->redirectorMock->expects(self::once())
            ->method('back')
            ->willReturn($redirectResponseMock);

        self::assertInstanceOf(
            RedirectResponse::class,
            $this->getController()->updateOrder($craftInventoryCategoryMock, $requestMock)
        );
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function updateOrderTestExceptionDataProvider(): array
    {
        return [
            'test updateOrder exception' => [
                'order',
                0,
                'error',
                'flash-messages.inventory-management.category.errors.updateOrder',
                'translation',
                new Exception('Test')
            ]
        ];
    }

    /** @dataProvider updateOrderTestExceptionDataProvider */
    public function testUpdateOrderException(
        string $expectedIntegerKey,
        int $expectedIntegerResult,
        string $expectedWithKey,
        string $expectedTranslationKey,
        string $expectedTranslation,
        Exception $thrownException
    ): void {
        $redirectResponseMock = $this->getMockBuilder(RedirectResponse::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['with'])
            ->getMock();

        $requestMock = $this->getMockBuilder(UpdateCraftInventoryCategoryOrderRequest::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['integer'])
            ->getMock();

        $craftInventoryCategoryMock = $this->getMockBuilder(CraftInventoryCategory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $requestMock->expects(self::once())
            ->method('integer')
            ->with($expectedIntegerKey)
            ->willReturn($expectedIntegerResult);

        $this->craftInventoryCategoryServiceMock->expects(self::once())
            ->method('updateOrder')
            ->with($craftInventoryCategoryMock, $expectedIntegerResult)
            ->willThrowException($thrownException);

        $this->loggerMock->expects(self::once())
            ->method('error')
            ->with(sprintf(
                'Could not update crafts inventory category order to: "%d" for reason: "%s"',
                $expectedIntegerResult,
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
            $this->getController()->updateOrder($craftInventoryCategoryMock, $requestMock)
        );
    }

    public function testForceDeleteSuccess(): void
    {
        $redirectResponseMock = $this->getMockBuilder(RedirectResponse::class)
            ->disableOriginalConstructor()
            ->getMock();

        $craftInventoryCategoryMock = $this->getMockBuilder(CraftInventoryCategory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->craftInventoryCategoryServiceMock->expects(self::once())
            ->method('forceDelete')
            ->with($craftInventoryCategoryMock)
            ->willReturn(true);

        $this->redirectorMock->expects(self::once())
            ->method('back')
            ->willReturn($redirectResponseMock);

        self::assertInstanceOf(
            RedirectResponse::class,
            $this->getController()->forceDelete($craftInventoryCategoryMock)
        );
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function forceDeleteFailureTestDataProvider(): array
    {
        return [
            'test forceDelete failure' => [
                'error',
                'flash-messages.inventory-management.category.errors.delete',
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

        $craftInventoryCategoryMock = $this->getMockBuilder(CraftInventoryCategory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->craftInventoryCategoryServiceMock->expects(self::once())
            ->method('forceDelete')
            ->with($craftInventoryCategoryMock)
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
            $this->getController()->forceDelete($craftInventoryCategoryMock)
        );
    }
}
