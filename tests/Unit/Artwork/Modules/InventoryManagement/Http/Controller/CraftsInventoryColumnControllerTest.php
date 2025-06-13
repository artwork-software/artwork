<?php

namespace Tests\Unit\Artwork\Modules\InventoryManagement\Http\Controller;

use Artwork\Modules\Inventory\Enums\CraftsInventoryColumnTypeEnum;
use Artwork\Modules\InventoryManagement\Http\Controllers\CraftsInventoryColumnController;
use Artwork\Modules\InventoryManagement\Http\Requests\Column\CreateCraftsInventoryColumnRequest;
use Artwork\Modules\InventoryManagement\Http\Requests\Column\DuplicateCraftsInventoryColumnRequest;
use Artwork\Modules\InventoryManagement\Http\Requests\Column\UpdateCraftsInventoryColumnBackgroundColorRequest;
use Artwork\Modules\InventoryManagement\Http\Requests\Column\UpdateCraftsInventoryColumnNameRequest;
use Artwork\Modules\InventoryManagement\Http\Requests\Column\UpdateCraftsInventoryColumnTypeOptionsRequest;
use Artwork\Modules\InventoryManagement\Models\CraftsInventoryColumn;
use Artwork\Modules\InventoryManagement\Services\CraftsInventoryColumnService;
use AssertionError;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;
use Illuminate\Translation\Translator;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class CraftsInventoryColumnControllerTest extends TestCase
{
    private readonly LoggerInterface $loggerMock;

    private readonly Redirector $redirectorMock;

    private readonly CraftsInventoryColumnService $craftsInventoryColumnServiceMock;

    private readonly Translator $translatorMock;

    protected function setUp(): void
    {
        $this->loggerMock = $this->getMockBuilder(NullLogger::class)
            ->onlyMethods(['error'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->redirectorMock = $this->getMockBuilder(Redirector::class)
            ->onlyMethods(['back'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->craftsInventoryColumnServiceMock = $this->getMockBuilder(CraftsInventoryColumnService::class)
            ->onlyMethods([
                'create',
                'duplicate',
                'updateName',
                'updateBackgroundColor',
                'updateTypeOptions',
                'forceDelete'
            ])
            ->disableOriginalConstructor()
            ->getMock();

        $this->translatorMock = $this->getMockBuilder(Translator::class)
            ->onlyMethods(['get'])
            ->disableOriginalConstructor()
            ->getMock();
    }

    private function getController(): CraftsInventoryColumnController
    {
        return new CraftsInventoryColumnController(
            $this->loggerMock,
            $this->redirectorMock,
            $this->craftsInventoryColumnServiceMock,
            $this->translatorMock
        );
    }

    /** @return array<string, array<int, mixed>> */
    public static function createSuccessTestDataProvider(): array
    {
        return [
            'test create' => [
                'name',
                'Test',
                'type.id',
                CraftsInventoryColumnTypeEnum::class,
                CraftsInventoryColumnTypeEnum::TEXT,
                'typeOptions',
                [],
                [],
                '',
                'defaultOption',
                '',
                ''
            ]
        ];
    }

    /**
     * @dataProvider createSuccessTestDataProvider
     */
    public function testCreateSuccess(
        string $expectedNameKey,
        string $expectedName,
        string $expectedTypeIdKey,
        string $expectedColumnTypeEnumClass,
        CraftsInventoryColumnTypeEnum $expectedColumnTypeEnum,
        string $expectedTypeOptionsKey,
        array $expectedTypeOptionsDefault,
        array $expectedTypeOptions,
        string $expectedBackgroundColor,
        string $expectedDefaultOptionKey,
        string $expectedDefaultOptionDefault,
        string $expectedDefaultOption
    ): void {
        $requestMock = $this->getMockBuilder(CreateCraftsInventoryColumnRequest::class)
            ->onlyMethods(['string', 'enum', 'get'])
            ->disableOriginalConstructor()
            ->getMock();

        $redirectResponseMock = $this->getMockBuilder(RedirectResponse::class)
            ->disableOriginalConstructor()
            ->getMock();

        $craftInventoryColumnMock = $this->getMockBuilder(CraftsInventoryColumn::class)
            ->disableOriginalConstructor()
            ->getMock();

        $requestMock->expects(self::once())
            ->method('enum')
            ->with($expectedTypeIdKey, $expectedColumnTypeEnumClass)
            ->willReturn($expectedColumnTypeEnum);

        $requestMock->expects(self::once())
            ->method('get')
            ->with($expectedTypeOptionsKey, $expectedTypeOptionsDefault)
            ->willReturn($expectedTypeOptions);

        $requestMock->expects($matcher = self::exactly(2))
            ->method('string')
            ->willReturnCallback(
                function (
                    string $key,
                    string|array|null $default
                ) use (
                    $matcher,
                    $expectedNameKey,
                    $expectedName,
                    $expectedDefaultOptionKey,
                    $expectedDefaultOptionDefault,
                    $expectedDefaultOption
                ): string|array {
                    switch ($matcher->numberOfInvocations()) {
                        case 1:
                            self::assertSame($expectedNameKey, $key);
                            self::assertNull($default);

                            return $expectedName;
                        case 2:
                            self::assertSame($expectedDefaultOptionKey, $key);
                            self::assertSame($expectedDefaultOptionDefault, $default);

                            return $expectedDefaultOption;
                        default:
                            throw new AssertionError('Number of invocations not expected.');
                    }
                }
            );

        $this->craftsInventoryColumnServiceMock->expects(self::once())
            ->method('create')
            ->with(
                $expectedName,
                $expectedColumnTypeEnum,
                $expectedDefaultOption,
                $expectedTypeOptions,
                $expectedBackgroundColor
            )->willReturn($craftInventoryColumnMock);

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
                'name',
                'Test',
                'type.id',
                CraftsInventoryColumnTypeEnum::class,
                CraftsInventoryColumnTypeEnum::TEXT,
                'typeOptions',
                [],
                [],
                '',
                'defaultOption',
                '',
                ''
            ]
        ];
    }

    /** @dataProvider createExceptionTestDataProvider */
    public function testCreateException(
        string $expectedNameKey,
        string $expectedName,
        string $expectedTypeIdKey,
        string $expectedColumnTypeEnumClass,
        CraftsInventoryColumnTypeEnum $expectedColumnTypeEnum,
        string $expectedTypeOptionsKey,
        array $expectedTypeOptionsDefault,
        array $expectedTypeOptions,
        string $expectedBackgroundColor,
        string $expectedDefaultOptionKey,
        string $expectedDefaultOptionDefault,
        string $expectedDefaultOption
    ): void {
        $redirectResponseMock = $this->getMockBuilder(RedirectResponse::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['with'])
            ->getMock();

        $requestMock = $this->getMockBuilder(CreateCraftsInventoryColumnRequest::class)
            ->onlyMethods(['string', 'enum', 'get'])
            ->disableOriginalConstructor()
            ->getMock();

        $requestMock->expects(self::once())
            ->method('get')
            ->with($expectedTypeOptionsKey, $expectedTypeOptionsDefault)
            ->willReturn($expectedTypeOptions);

        $requestMock->expects(self::once())
            ->method('enum')
            ->with($expectedTypeIdKey, $expectedColumnTypeEnumClass)
            ->willReturn($expectedColumnTypeEnum);

        $requestMock->expects($matcher = self::exactly(2))
            ->method('string')
            ->willReturnCallback(
                function (
                    string $key,
                    string|array|null $default
                ) use (
                    $matcher,
                    $expectedNameKey,
                    $expectedName,
                    $expectedDefaultOptionKey,
                    $expectedDefaultOptionDefault,
                    $expectedDefaultOption
                ): string|array {
                    switch ($matcher->numberOfInvocations()) {
                        case 1:
                            self::assertSame($expectedNameKey, $key);
                            self::assertNull($default);

                            return $expectedName;
                        case 2:
                            self::assertSame($expectedDefaultOptionKey, $key);
                            self::assertSame($expectedDefaultOptionDefault, $default);

                            return $expectedDefaultOption;
                        default:
                            throw new AssertionError('Number of invocations not expected.');
                    }
                }
            );

        $this->craftsInventoryColumnServiceMock->expects(self::once())
            ->method('create')
            ->with(
                $expectedName,
                $expectedColumnTypeEnum,
                $expectedDefaultOption,
                $expectedTypeOptions,
                $expectedBackgroundColor,
            )->willThrowException(new Exception('Test'));

        $this->loggerMock->expects(self::once())
            ->method('error')
            ->with('Could not create crafts inventory column for reason: "Test"');

        $this->translatorMock->expects(self::once())
            ->method('get')
            ->with('flash-messages.inventory-management.column.errors.create')
            ->willReturn('translation');

        $this->redirectorMock->expects(self::once())
            ->method('back')
            ->willReturn($redirectResponseMock);

        $redirectResponseMock->expects(self::once())
            ->method('with')
            ->with('error', 'translation')
            ->willReturn($redirectResponseMock);

        self::assertInstanceOf(
            RedirectResponse::class,
            $this->getController()->create($requestMock)
        );
    }

    /** @return array<string, array<int, mixed>> */
    public static function duplicateSuccessTestDataProvider(): array
    {
        return [
            'test duplicate success' => [
                'columnId',
                1
            ]
        ];
    }

    /**
     * @dataProvider duplicateSuccessTestDataProvider
     */
    public function testDuplicateSuccess(
        string $expectedColumnIdKey,
        int $expectedColumnId
    ): void {
        $requestMock = $this->getMockBuilder(DuplicateCraftsInventoryColumnRequest::class)
            ->onlyMethods(['integer'])
            ->disableOriginalConstructor()
            ->getMock();

        $redirectResponseMock = $this->getMockBuilder(RedirectResponse::class)
            ->disableOriginalConstructor()
            ->getMock();

        $craftInventoryColumnMock = $this->getMockBuilder(CraftsInventoryColumn::class)
            ->disableOriginalConstructor()
            ->getMock();

        $requestMock->expects(self::once())
            ->method('integer')
            ->with($expectedColumnIdKey)
            ->willReturn($expectedColumnId);

        $this->craftsInventoryColumnServiceMock->expects(self::once())
            ->method('duplicate')
            ->with($expectedColumnId)->willReturn($craftInventoryColumnMock);

        $this->redirectorMock->expects(self::once())
            ->method('back')
            ->willReturn($redirectResponseMock);

        self::assertInstanceOf(
            RedirectResponse::class,
            $this->getController()->duplicate($requestMock)
        );
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function duplicateExceptionTestDataProvider(): array
    {
        return [
            'test duplicate success' => [
                'columnId',
                1,
                'error',
                'flash-messages.inventory-management.column.errors.duplicate',
                'translation'
            ]
        ];
    }

    /** @dataProvider duplicateExceptionTestDataProvider */
    public function testDuplicateException(
        string $expectedColumnIdKey,
        int $expectedColumnId,
        string $expectedWithKey,
        string $expectedTranslationKey,
        string $expectedTranslation
    ): void {
        $redirectResponseMock = $this->getMockBuilder(RedirectResponse::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['with'])
            ->getMock();

        $requestMock = $this->getMockBuilder(DuplicateCraftsInventoryColumnRequest::class)
            ->onlyMethods(['integer'])
            ->disableOriginalConstructor()
            ->getMock();

        $requestMock->expects(self::once())
            ->method('integer')
            ->with($expectedColumnIdKey)
            ->willReturn($expectedColumnId);

        $this->craftsInventoryColumnServiceMock->expects(self::once())
            ->method('duplicate')
            ->with($expectedColumnId)
            ->willThrowException(new Exception('Test'));

        $this->loggerMock->expects(self::once())
            ->method('error')
            ->with(sprintf(
                'Could not duplicate crafts inventory column with id "%d" for reason: "%s"',
                $expectedColumnId,
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
            $this->getController()->duplicate($requestMock)
        );
    }

    /** @return array<string, array<int, mixed>> */
    public static function updateNameSuccessTestDataProvider(): array
    {
        return [
            'test updateName success' => [
                'name',
                'Test'
            ]
        ];
    }

    /**
     * @dataProvider updateNameSuccessTestDataProvider
     */
    public function testUpdateNameDuplicateSuccess(
        string $expectedNameKey,
        string $expectedName
    ): void {
        $requestMock = $this->getMockBuilder(UpdateCraftsInventoryColumnNameRequest::class)
            ->onlyMethods(['string'])
            ->disableOriginalConstructor()
            ->getMock();

        $redirectResponseMock = $this->getMockBuilder(RedirectResponse::class)
            ->disableOriginalConstructor()
            ->getMock();

        $craftInventoryColumnMock = $this->getMockBuilder(CraftsInventoryColumn::class)
            ->disableOriginalConstructor()
            ->getMock();

        $requestMock->expects(self::once())
            ->method('string')
            ->with($expectedNameKey)
            ->willReturn($expectedName);

        $this->craftsInventoryColumnServiceMock->expects(self::once())
            ->method('updateName')
            ->with($expectedName, $craftInventoryColumnMock);

        $this->redirectorMock->expects(self::once())
            ->method('back')
            ->willReturn($redirectResponseMock);

        self::assertInstanceOf(
            RedirectResponse::class,
            $this->getController()->updateName($craftInventoryColumnMock, $requestMock)
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
                'flash-messages.inventory-management.column.errors.updateName',
                'translation'
            ]
        ];
    }

    /** @dataProvider updateNameExceptionTestDataProvider */
    public function testUpdateNameDuplicateException(
        string $expectedNameKey,
        string $expectedName,
        string $expectedWithKey,
        string $expectedTranslationKey,
        string $expectedTranslation
    ): void {
        $redirectResponseMock = $this->getMockBuilder(RedirectResponse::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['with'])
            ->getMock();

        $craftInventoryColumnMock = $this->getMockBuilder(CraftsInventoryColumn::class)
            ->disableOriginalConstructor()
            ->getMock();

        $requestMock = $this->getMockBuilder(UpdateCraftsInventoryColumnNameRequest::class)
            ->onlyMethods(['string'])
            ->disableOriginalConstructor()
            ->getMock();

        $requestMock->expects(self::once())
            ->method('string')
            ->with($expectedNameKey)
            ->willReturn($expectedName);

        $this->craftsInventoryColumnServiceMock->expects(self::once())
            ->method('updateName')
            ->with($expectedName, $craftInventoryColumnMock)
            ->willThrowException(new Exception('Test'));

        $this->loggerMock->expects(self::once())
            ->method('error')
            ->with(sprintf(
                'Could not update crafts inventory column name to: "%s" for reason: "%s"',
                $expectedName,
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
            $this->getController()->updateName($craftInventoryColumnMock, $requestMock)
        );
    }

    /** @return array<string, array<int, mixed>> */
    public static function updateBackgroundColorSuccessTestDataProvider(): array
    {
        return [
            'test updateBackgroundColor success' => [
                'background_color',
                'Test'
            ]
        ];
    }

    /**
     * @dataProvider updateBackgroundColorSuccessTestDataProvider
     */
    public function testUpdateBackgroundColorSuccess(
        string $expectedBackgroundColorKey,
        string $expectedBackgroundColor
    ): void {
        $requestMock = $this->getMockBuilder(UpdateCraftsInventoryColumnBackgroundColorRequest::class)
            ->onlyMethods(['string'])
            ->disableOriginalConstructor()
            ->getMock();

        $redirectResponseMock = $this->getMockBuilder(RedirectResponse::class)
            ->disableOriginalConstructor()
            ->getMock();

        $craftInventoryColumnMock = $this->getMockBuilder(CraftsInventoryColumn::class)
            ->disableOriginalConstructor()
            ->getMock();

        $requestMock->expects(self::once())
            ->method('string')
            ->with($expectedBackgroundColorKey)
            ->willReturn($expectedBackgroundColor);

        $this->craftsInventoryColumnServiceMock->expects(self::once())
            ->method('updateBackgroundColor')
            ->with($expectedBackgroundColor, $craftInventoryColumnMock);

        $this->redirectorMock->expects(self::once())
            ->method('back')
            ->willReturn($redirectResponseMock);

        self::assertInstanceOf(
            RedirectResponse::class,
            $this->getController()->updateBackgroundColor($craftInventoryColumnMock, $requestMock)
        );
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function updateBackgroundColorExceptionTestDataProvider(): array
    {
        return [
            'test updateBackgroundColor exception' => [
                'background_color',
                'Test',
                'error',
                'flash-messages.inventory-management.column.errors.updateBackgroundColor',
                'translation'
            ]
        ];
    }

    /** @dataProvider updateBackgroundColorExceptionTestDataProvider */
    public function testUpdateBackgroundColorException(
        string $expectedBackgroundColorKey,
        string $expectedBackgroundColor,
        string $expectedWithKey,
        string $expectedTranslationKey,
        string $expectedTranslation
    ): void {
        $redirectResponseMock = $this->getMockBuilder(RedirectResponse::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['with'])
            ->getMock();

        $craftInventoryColumnMock = $this->getMockBuilder(CraftsInventoryColumn::class)
            ->disableOriginalConstructor()
            ->getMock();

        $requestMock = $this->getMockBuilder(UpdateCraftsInventoryColumnBackgroundColorRequest::class)
            ->onlyMethods(['string'])
            ->disableOriginalConstructor()
            ->getMock();

        $requestMock->expects(self::once())
            ->method('string')
            ->with($expectedBackgroundColorKey)
            ->willReturn($expectedBackgroundColor);

        $this->craftsInventoryColumnServiceMock->expects(self::once())
            ->method('updateBackgroundColor')
            ->with($expectedBackgroundColor, $craftInventoryColumnMock)
            ->willThrowException(new Exception('Test'));

        $this->loggerMock->expects(self::once())
            ->method('error')
            ->with(sprintf(
                'Could not update crafts inventory column background color to: "%s" for reason: "%s"',
                $expectedBackgroundColor,
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
            $this->getController()->updateBackgroundColor($craftInventoryColumnMock, $requestMock)
        );
    }

    /** @return array<string, array<int, mixed>> */
    public static function updateTypeOptionsSuccessTestDataProvider(): array
    {
        return [
            'test updateTypeOptions success' => [
                'selectOptions',
                []
            ]
        ];
    }

    /**
     * @dataProvider updateTypeOptionsSuccessTestDataProvider
     */
    public function testUpdateTypeOptionsSuccess(
        string $expectedSelectOptionsKey,
        array $expectedSelectOptions
    ): void {
        $requestMock = $this->getMockBuilder(UpdateCraftsInventoryColumnTypeOptionsRequest::class)
            ->onlyMethods(['get'])
            ->disableOriginalConstructor()
            ->getMock();

        $redirectResponseMock = $this->getMockBuilder(RedirectResponse::class)
            ->disableOriginalConstructor()
            ->getMock();

        $craftInventoryColumnMock = $this->getMockBuilder(CraftsInventoryColumn::class)
            ->disableOriginalConstructor()
            ->getMock();

        $requestMock->expects(self::once())
            ->method('get')
            ->with($expectedSelectOptionsKey)
            ->willReturn($expectedSelectOptions);

        $this->craftsInventoryColumnServiceMock->expects(self::once())
            ->method('updateTypeOptions')
            ->with($expectedSelectOptions, $craftInventoryColumnMock);

        $this->redirectorMock->expects(self::once())
            ->method('back')
            ->willReturn($redirectResponseMock);

        self::assertInstanceOf(
            RedirectResponse::class,
            $this->getController()->updateTypeOptions($craftInventoryColumnMock, $requestMock)
        );
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function updateTypeOptionsExceptionTestDataProvider(): array
    {
        return [
            'test updateBackgroundColor exception' => [
                'selectOptions',
                [],
                'error',
                'flash-messages.inventory-management.column.errors.updateTypeOptions',
                'translation'
            ]
        ];
    }

    /** @dataProvider updateTypeOptionsExceptionTestDataProvider */
    public function testUpdateTypeOptionsException(
        string $expectedSelectOptionsKey,
        array $expectedSelectOptions,
        string $expectedWithKey,
        string $expectedTranslationKey,
        string $expectedTranslation
    ): void {
        $redirectResponseMock = $this->getMockBuilder(RedirectResponse::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['with'])
            ->getMock();

        $craftInventoryColumnMock = $this->getMockBuilder(CraftsInventoryColumn::class)
            ->disableOriginalConstructor()
            ->getMock();

        $requestMock = $this->getMockBuilder(UpdateCraftsInventoryColumnTypeOptionsRequest::class)
            ->onlyMethods(['get'])
            ->disableOriginalConstructor()
            ->getMock();

        $requestMock->expects(self::once())
            ->method('get')
            ->with($expectedSelectOptionsKey)
            ->willReturn($expectedSelectOptions);

        $this->craftsInventoryColumnServiceMock->expects(self::once())
            ->method('updateTypeOptions')
            ->with($expectedSelectOptions, $craftInventoryColumnMock)
            ->willThrowException(new Exception('Test'));

        $this->loggerMock->expects(self::once())
            ->method('error')
            ->with(sprintf(
                'Could not update crafts inventory column type options to: "%s" for reason: "%s"',
                implode(',', $expectedSelectOptions),
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
            $this->getController()->updateTypeOptions($craftInventoryColumnMock, $requestMock)
        );
    }

    public function testForceDeleteSuccess(): void
    {
        $redirectResponseMock = $this->getMockBuilder(RedirectResponse::class)
            ->disableOriginalConstructor()
            ->getMock();

        $craftInventoryColumnMock = $this->getMockBuilder(CraftsInventoryColumn::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->craftsInventoryColumnServiceMock->expects(self::once())
            ->method('forceDelete')
            ->with($craftInventoryColumnMock)
            ->willReturn(true);

        $this->redirectorMock->expects(self::once())
            ->method('back')
            ->willReturn($redirectResponseMock);

        self::assertInstanceOf(
            RedirectResponse::class,
            $this->getController()->forceDelete($craftInventoryColumnMock)
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
                'flash-messages.inventory-management.column.errors.delete',
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

        $craftInventoryColumnMock = $this->getMockBuilder(CraftsInventoryColumn::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->craftsInventoryColumnServiceMock->expects(self::once())
            ->method('forceDelete')
            ->with($craftInventoryColumnMock)
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
            $this->getController()->forceDelete($craftInventoryColumnMock)
        );
    }
}
