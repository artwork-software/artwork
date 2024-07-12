<?php

namespace Tests\Unit\Artwork\Modules\InventoryManagement\Http\Controller;

use Artwork\Modules\InventoryManagement\Http\Controller\CraftInventoryGroupController;
use Artwork\Modules\InventoryManagement\Http\Requests\Group\CreateCraftInventoryGroupRequest;
use Artwork\Modules\InventoryManagement\Http\Requests\Group\UpdateCraftInventoryGroupNameRequest;
use Artwork\Modules\InventoryManagement\Http\Requests\Group\UpdateCraftInventoryGroupOrderRequest;
use Artwork\Modules\InventoryManagement\Models\CraftInventoryGroup;
use Artwork\Modules\InventoryManagement\Services\CraftInventoryGroupService;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;
use Illuminate\Translation\Translator;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class CraftInventoryGroupControllerTest extends TestCase
{
    private readonly LoggerInterface $loggerMock;

    private readonly Redirector $redirectorMock;

    private readonly CraftInventoryGroupService $craftInventoryGroupServiceMock;

    private readonly Translator $translatorMock;

    protected function setUp(): void
    {
        $this->translatorMock = $this->getMockBuilder(Translator::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['get'])
            ->getMock();

        $this->loggerMock = $this->getMockBuilder(NullLogger::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['error'])
            ->getMock();

        $this->redirectorMock = $this->getMockBuilder(Redirector::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['back'])
            ->getMock();

        $this->craftInventoryGroupServiceMock = $this->getMockBuilder(CraftInventoryGroupService::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create', 'updateName', 'updateOrder', 'forceDelete'])
            ->getMock();
    }

    private function getController(): CraftInventoryGroupController
    {
        return new CraftInventoryGroupController(
            $this->loggerMock,
            $this->redirectorMock,
            $this->craftInventoryGroupServiceMock,
            $this->translatorMock
        );
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function createTestSuccessDataProvider(): array
    {
        return [
            'test create success' => [
                'categoryId',
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

        $craftInventoryGroupMock = $this->getMockBuilder(CraftInventoryGroup::class)
            ->disableOriginalConstructor()
            ->getMock();

        $requestMock = $this->getMockBuilder(CreateCraftInventoryGroupRequest::class)
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

        $this->craftInventoryGroupServiceMock->expects(self::once())
            ->method('create')
            ->with($expectedIntegerResult, $expectedStringResult)
            ->willReturn($craftInventoryGroupMock);

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
                'categoryId',
                1,
                'name',
                'Test',
                'error',
                'flash-messages.inventory-management.group.errors.create',
                'translation',
                new Exception('Test')
            ]
        ];
    }

    /** @dataProvider createExceptionTestDataProvider */
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
        $redirectResponseMock = $this->getMockBuilder(RedirectResponse::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['with'])
            ->getMock();

        $requestMock = $this->getMockBuilder(CreateCraftInventoryGroupRequest::class)
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

        $this->craftInventoryGroupServiceMock->expects(self::once())
            ->method('create')
            ->with($expectedIntegerResult, $expectedStringResult)
            ->willThrowException($thrownException);

        $this->loggerMock->expects(self::once())
            ->method('error')
            ->with('Could not create crafts inventory group for reason: "Test"');

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
    public static function updateNameSuccessTestDataProvider(): array
    {
        return [
            'test updateName success' => [
                'name',
                'Test',
            ]
        ];
    }

    /** @dataProvider updateNameSuccessTestDataProvider */
    public function testUpdateNameSuccess(
        string $expectedStringKey,
        string $expectedStringResult
    ): void {
        $requestMock = $this->getMockBuilder(UpdateCraftInventoryGroupNameRequest::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['string'])
            ->getMock();

        $redirectResponseMock = $this->getMockBuilder(RedirectResponse::class)
            ->disableOriginalConstructor()
            ->getMock();

        $craftInventoryGroupMock = $this->getMockBuilder(CraftInventoryGroup::class)
            ->disableOriginalConstructor()
            ->getMock();

        $requestMock->expects(self::once())
            ->method('string')
            ->with($expectedStringKey)
            ->willReturn($expectedStringResult);

        $this->craftInventoryGroupServiceMock->expects(self::once())
            ->method('updateName')
            ->with($expectedStringResult, $craftInventoryGroupMock);

        $this->redirectorMock->expects(self::once())
            ->method('back')
            ->willReturn($redirectResponseMock);

        self::assertInstanceOf(
            RedirectResponse::class,
            $this->getController()->updateName($craftInventoryGroupMock, $requestMock)
        );
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function updateNameExceptionTestDataProvider(): array
    {
        return [
            'test updateName exception' => [
                'name',
                'Test',
                'error',
                'flash-messages.inventory-management.group.errors.updateName',
                'translation',
                new Exception('Test')
            ]
        ];
    }

    /** @dataProvider updateNameExceptionTestDataProvider */
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

        $requestMock = $this->getMockBuilder(UpdateCraftInventoryGroupNameRequest::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['string'])
            ->getMock();

        $craftInventoryGroupMock = $this->getMockBuilder(CraftInventoryGroup::class)
            ->disableOriginalConstructor()
            ->getMock();

        $requestMock->expects(self::once())
            ->method('string')
            ->with($expectedStringKey)
            ->willReturn($expectedStringResult);

        $this->craftInventoryGroupServiceMock->expects(self::once())
            ->method('updateName')
            ->with($expectedStringResult, $craftInventoryGroupMock)
            ->willThrowException($thrownException);

        $this->loggerMock->expects(self::once())
            ->method('error')
            ->with(sprintf(
                'Could not update crafts inventory group name to: "%s" for reason: "%s"',
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
            $this->getController()->updateName($craftInventoryGroupMock, $requestMock)
        );
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function updateOrderSuccessTestDataProvider(): array
    {
        return [
            'test updateOrder success' => [
                'order',
                0,
            ]
        ];
    }

    /** @dataProvider updateOrderSuccessTestDataProvider */
    public function testUpdateOrderSuccess(
        string $expectedIntegerKey,
        int $expectedIntegerResult
    ): void {
        $requestMock = $this->getMockBuilder(UpdateCraftInventoryGroupOrderRequest::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['integer'])
            ->getMock();

        $redirectResponseMock = $this->getMockBuilder(RedirectResponse::class)
            ->disableOriginalConstructor()
            ->getMock();

        $craftInventoryGroupMock = $this->getMockBuilder(CraftInventoryGroup::class)
            ->disableOriginalConstructor()
            ->getMock();

        $requestMock->expects(self::once())
            ->method('integer')
            ->with($expectedIntegerKey)
            ->willReturn($expectedIntegerResult);

        $this->craftInventoryGroupServiceMock->expects(self::once())
            ->method('updateOrder')
            ->with($craftInventoryGroupMock, $expectedIntegerResult);

        $this->redirectorMock->expects(self::once())
            ->method('back')
            ->willReturn($redirectResponseMock);

        self::assertInstanceOf(
            RedirectResponse::class,
            $this->getController()->updateOrder($craftInventoryGroupMock, $requestMock)
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
                'flash-messages.inventory-management.group.errors.updateOrder',
                'translation',
                new Exception('Test')
            ]
        ];
    }

    /** @dataProvider updateOrderExceptionTestDataProvider */
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

        $requestMock = $this->getMockBuilder(UpdateCraftInventoryGroupOrderRequest::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['integer'])
            ->getMock();

        $craftInventoryGroupMock = $this->getMockBuilder(CraftInventoryGroup::class)
            ->disableOriginalConstructor()
            ->getMock();

        $requestMock->expects(self::once())
            ->method('integer')
            ->with($expectedIntegerKey)
            ->willReturn($expectedIntegerResult);

        $this->craftInventoryGroupServiceMock->expects(self::once())
            ->method('updateOrder')
            ->with($craftInventoryGroupMock, $expectedIntegerResult)
            ->willThrowException($thrownException);

        $this->loggerMock->expects(self::once())
            ->method('error')
            ->with(sprintf(
                'Could not update crafts inventory group order to: "%s" for reason: "%s"',
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
            $this->getController()->updateOrder($craftInventoryGroupMock, $requestMock)
        );
    }

    public function testForceDeleteSuccess(): void
    {
        $redirectResponseMock = $this->getMockBuilder(RedirectResponse::class)
            ->disableOriginalConstructor()
            ->getMock();

        $craftInventoryGroupMock = $this->getMockBuilder(CraftInventoryGroup::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->craftInventoryGroupServiceMock->expects(self::once())
            ->method('forceDelete')
            ->with($craftInventoryGroupMock)
            ->willReturn(true);

        $this->redirectorMock->expects(self::once())
            ->method('back')
            ->willReturn($redirectResponseMock);

        self::assertInstanceOf(
            RedirectResponse::class,
            $this->getController()->forceDelete($craftInventoryGroupMock)
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
                'flash-messages.inventory-management.group.errors.delete',
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

        $craftInventoryGroupMock = $this->getMockBuilder(CraftInventoryGroup::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->craftInventoryGroupServiceMock->expects(self::once())
            ->method('forceDelete')
            ->with($craftInventoryGroupMock)
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
            $this->getController()->forceDelete($craftInventoryGroupMock)
        );
    }
}
