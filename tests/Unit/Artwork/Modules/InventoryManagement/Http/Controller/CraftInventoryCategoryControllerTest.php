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
use Psr\Log\NullLogger;

class CraftInventoryCategoryControllerTest extends TestCase
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public static function createTestSuccessDataProvider(): array
    {
        return [
            'test creat success' => [
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
        $redirectorMock = $this->getMockBuilder(Redirector::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['back'])
            ->getMock();

        $craftInventoryCategoryServiceMock = $this->getMockBuilder(CraftInventoryCategoryService::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();

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

        $craftInventoryCategoryServiceMock->expects(self::once())
            ->method('create')
            ->with($expectedIntegerResult, $expectedStringResult)
            ->willReturn($craftInventoryCategoryMock);

        $redirectorMock->expects(self::once())
            ->method('back')
            ->willReturn($redirectResponseMock);

        (new CraftInventoryCategoryController(
            $this->getMockBuilder(Translator::class)
                ->disableOriginalConstructor()
                ->getMock(),
            $this->getMockBuilder(NullLogger::class)
                ->disableOriginalConstructor()
                ->getMock(),
            $redirectorMock,
            $craftInventoryCategoryServiceMock
        ))->create($requestMock);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function createTestExceptionDataProvider(): array
    {
        return [
            'test creat exception' => [
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
        $translatorMock = $this->getMockBuilder(Translator::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['get'])
            ->getMock();

        $loggerMock = $this->getMockBuilder(NullLogger::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['error'])
            ->getMock();

        $redirectorMock = $this->getMockBuilder(Redirector::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['back'])
            ->getMock();

        $craftInventoryCategoryServiceMock = $this->getMockBuilder(CraftInventoryCategoryService::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
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

        $craftInventoryCategoryServiceMock->expects(self::once())
            ->method('create')
            ->with($expectedIntegerResult, $expectedStringResult)
            ->willThrowException($thrownException);

        $loggerMock->expects(self::once())
            ->method('error')
            ->with('Could not create crafts inventory category for reason: "Test"');

        $translatorMock->expects(self::once())
            ->method('get')
            ->with($expectedTranslationKey)
            ->willReturn($expectedTranslation);

        $redirectorMock->expects(self::once())
            ->method('back')
            ->willReturn($redirectResponseMock);

        $redirectResponseMock->expects(self::once())
            ->method('with')
            ->with($expectedWithKey, $expectedTranslation)
            ->willReturn($redirectResponseMock);

        (new CraftInventoryCategoryController(
            $translatorMock,
            $loggerMock,
            $redirectorMock,
            $craftInventoryCategoryServiceMock
        ))->create($requestMock);
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
        $redirectorMock = $this->getMockBuilder(Redirector::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['back'])
            ->getMock();

        $craftInventoryCategoryServiceMock = $this->getMockBuilder(CraftInventoryCategoryService::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['updateName'])
            ->getMock();

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

        $craftInventoryCategoryServiceMock->expects(self::once())
            ->method('updateName')
            ->with($expectedStringResult, $craftInventoryCategoryMock);

        $redirectorMock->expects(self::once())
            ->method('back')
            ->willReturn($redirectResponseMock);

        (new CraftInventoryCategoryController(
            $this->getMockBuilder(Translator::class)
                ->disableOriginalConstructor()
                ->getMock(),
            $this->getMockBuilder(NullLogger::class)
                ->disableOriginalConstructor()
                ->getMock(),
            $redirectorMock,
            $craftInventoryCategoryServiceMock
        ))->updateName($craftInventoryCategoryMock, $requestMock);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function updateNameTestExceptionDataProvider(): array
    {
        return [
            'test creat exception' => [
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
        $translatorMock = $this->getMockBuilder(Translator::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['get'])
            ->getMock();

        $loggerMock = $this->getMockBuilder(NullLogger::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['error'])
            ->getMock();

        $redirectorMock = $this->getMockBuilder(Redirector::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['back'])
            ->getMock();

        $craftInventoryCategoryServiceMock = $this->getMockBuilder(CraftInventoryCategoryService::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['updateName'])
            ->getMock();

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

        $craftInventoryCategoryServiceMock->expects(self::once())
            ->method('updateName')
            ->with($expectedStringResult, $craftInventoryCategoryMock)
            ->willThrowException($thrownException);

        $loggerMock->expects(self::once())
            ->method('error')
            ->with(sprintf(
                'Could not update crafts inventory category name to: "%s" for reason: "%s"',
                $expectedStringResult,
                'Test'
            ));

        $translatorMock->expects(self::once())
            ->method('get')
            ->with($expectedTranslationKey)
            ->willReturn($expectedTranslation);

        $redirectorMock->expects(self::once())
            ->method('back')
            ->willReturn($redirectResponseMock);

        $redirectResponseMock->expects(self::once())
            ->method('with')
            ->with($expectedWithKey, $expectedTranslation)
            ->willReturn($redirectResponseMock);

        (new CraftInventoryCategoryController(
            $translatorMock,
            $loggerMock,
            $redirectorMock,
            $craftInventoryCategoryServiceMock
        ))->updateName($craftInventoryCategoryMock, $requestMock);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function updateOrderTestSuccessDataProvider(): array
    {
        return [
            'test updateName success' => [
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
        $redirectorMock = $this->getMockBuilder(Redirector::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['back'])
            ->getMock();

        $craftInventoryCategoryServiceMock = $this->getMockBuilder(CraftInventoryCategoryService::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['updateOrder'])
            ->getMock();

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

        $craftInventoryCategoryServiceMock->expects(self::once())
            ->method('updateOrder')
            ->with($craftInventoryCategoryMock, $expectedIntegerResult);

        $redirectorMock->expects(self::once())
            ->method('back')
            ->willReturn($redirectResponseMock);

        (new CraftInventoryCategoryController(
            $this->getMockBuilder(Translator::class)
                ->disableOriginalConstructor()
                ->getMock(),
            $this->getMockBuilder(NullLogger::class)
                ->disableOriginalConstructor()
                ->getMock(),
            $redirectorMock,
            $craftInventoryCategoryServiceMock
        ))->updateOrder($craftInventoryCategoryMock, $requestMock);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function updateOrderTestExceptionDataProvider(): array
    {
        return [
            'test creat exception' => [
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
        $translatorMock = $this->getMockBuilder(Translator::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['get'])
            ->getMock();

        $loggerMock = $this->getMockBuilder(NullLogger::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['error'])
            ->getMock();

        $redirectorMock = $this->getMockBuilder(Redirector::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['back'])
            ->getMock();

        $craftInventoryCategoryServiceMock = $this->getMockBuilder(CraftInventoryCategoryService::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['updateOrder'])
            ->getMock();

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

        $craftInventoryCategoryServiceMock->expects(self::once())
            ->method('updateOrder')
            ->with($craftInventoryCategoryMock, $expectedIntegerResult)
            ->willThrowException($thrownException);

        $loggerMock->expects(self::once())
            ->method('error')
            ->with(sprintf(
                'Could not update crafts inventory category order to: "%d" for reason: "%s"',
                $expectedIntegerResult,
                'Test'
            ));

        $translatorMock->expects(self::once())
            ->method('get')
            ->with($expectedTranslationKey)
            ->willReturn($expectedTranslation);

        $redirectorMock->expects(self::once())
            ->method('back')
            ->willReturn($redirectResponseMock);

        $redirectResponseMock->expects(self::once())
            ->method('with')
            ->with($expectedWithKey, $expectedTranslation)
            ->willReturn($redirectResponseMock);

        (new CraftInventoryCategoryController(
            $translatorMock,
            $loggerMock,
            $redirectorMock,
            $craftInventoryCategoryServiceMock
        ))->updateOrder($craftInventoryCategoryMock, $requestMock);
    }

    public function testForceDeleteSuccess(): void
    {
        $redirectorMock = $this->getMockBuilder(Redirector::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['back'])
            ->getMock();

        $craftInventoryCategoryServiceMock = $this->getMockBuilder(CraftInventoryCategoryService::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['forceDelete'])
            ->getMock();

        $redirectResponseMock = $this->getMockBuilder(RedirectResponse::class)
            ->disableOriginalConstructor()
            ->getMock();

        $craftInventoryCategoryMock = $this->getMockBuilder(CraftInventoryCategory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $craftInventoryCategoryServiceMock->expects(self::once())
            ->method('forceDelete')
            ->with($craftInventoryCategoryMock)
            ->willReturn(true);

        $redirectorMock->expects(self::once())
            ->method('back')
            ->willReturn($redirectResponseMock);

        (new CraftInventoryCategoryController(
            $this->getMockBuilder(Translator::class)
                ->disableOriginalConstructor()
                ->getMock(),
            $this->getMockBuilder(NullLogger::class)
                ->disableOriginalConstructor()
                ->getMock(),
            $redirectorMock,
            $craftInventoryCategoryServiceMock
        ))->forceDelete($craftInventoryCategoryMock);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function forceDeleteFailureTestDataProvider(): array
    {
        return [
            'test creat exception' => [
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
        $translatorMock = $this->getMockBuilder(Translator::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['get'])
            ->getMock();

        $redirectorMock = $this->getMockBuilder(Redirector::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['back'])
            ->getMock();

        $craftInventoryCategoryServiceMock = $this->getMockBuilder(CraftInventoryCategoryService::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['forceDelete'])
            ->getMock();

        $redirectResponseMock = $this->getMockBuilder(RedirectResponse::class)
            ->onlyMethods(['with'])
            ->disableOriginalConstructor()
            ->getMock();

        $craftInventoryCategoryMock = $this->getMockBuilder(CraftInventoryCategory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $translatorMock = $this->getMockBuilder(Translator::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['get'])
            ->getMock();

        $craftInventoryCategoryServiceMock->expects(self::once())
            ->method('forceDelete')
            ->with($craftInventoryCategoryMock)
            ->willReturn(false);

        $redirectorMock->expects(self::once())
            ->method('back')
            ->willReturn($redirectResponseMock);

        $translatorMock->expects(self::once())
            ->method('get')
            ->with($expectedTranslationKey)
            ->willReturn($expectedTranslation);

        $redirectResponseMock->expects(self::once())
            ->method('with')
            ->with($expectedWithKey, $expectedTranslation)
            ->willReturn($redirectResponseMock);

        (new CraftInventoryCategoryController(
            $translatorMock,
            $this->getMockBuilder(NullLogger::class)
                ->disableOriginalConstructor()
                ->getMock(),
            $redirectorMock,
            $craftInventoryCategoryServiceMock
        ))->forceDelete($craftInventoryCategoryMock);
    }
}
