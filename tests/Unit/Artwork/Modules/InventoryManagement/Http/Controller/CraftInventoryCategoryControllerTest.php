<?php

namespace Tests\Unit\Artwork\Modules\InventoryManagement\Http\Controller;

use Artwork\Modules\InventoryManagement\Http\Controller\CraftInventoryCategoryController;
use Artwork\Modules\InventoryManagement\Http\Requests\Category\CreateCraftInventoryCategoryRequest;
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

        $craftCategoryMock = $this->getMockBuilder(CraftInventoryCategory::class)
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
            ->willReturn($craftCategoryMock);

        $redirectorMock->expects(self::once())
            ->method('back')
            ->willReturn($redirectResponseMock);

        (new CraftInventoryCategoryController(
            $this->getMockBuilder(Translator::class)
                ->disableOriginalConstructor()
                ->onlyMethods(['get'])
                ->getMock(),
            $this->getMockBuilder(NullLogger::class)
                ->disableOriginalConstructor()
                ->onlyMethods(['error'])
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
}
