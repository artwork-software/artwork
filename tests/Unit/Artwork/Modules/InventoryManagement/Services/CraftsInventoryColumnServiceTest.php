<?php

namespace Tests\Unit\Artwork\Modules\InventoryManagement\Services;

use Artwork\Modules\Inventory\Enums\CraftsInventoryColumnTypeEnum;
use Artwork\Modules\InventoryManagement\Models\CraftInventoryItemCell;
use Artwork\Modules\InventoryManagement\Models\CraftsInventoryColumn;
use Artwork\Modules\InventoryManagement\Repositories\CraftsInventoryColumnRepository;
use Artwork\Modules\InventoryManagement\Services\CraftInventoryItemCellService;
use Artwork\Modules\InventoryManagement\Services\CraftInventoryItemService;
use Artwork\Modules\InventoryManagement\Services\CraftsInventoryColumnService;
use AssertionError;
use Illuminate\Database\Eloquent\Collection;
use Exception;
use PHPUnit\Framework\TestCase;
use Throwable;

class CraftsInventoryColumnServiceTest extends TestCase
{
    private readonly CraftsInventoryColumnRepository $craftsInventoryColumnRepositoryMock;

    private readonly CraftInventoryItemService $craftInventoryItemServiceMock;

    private readonly CraftInventoryItemCellService $craftInventoryItemCellServiceMock;

    protected function setUp(): void
    {
        $this->craftsInventoryColumnRepositoryMock = $this
            ->getMockBuilder(CraftsInventoryColumnRepository::class)
            ->onlyMethods([
                'find',
                'replicate',
                'getNewModelInstance',
                'getAllOrdered',
                'getAllItemCells',
                'saveOrFail',
                'updateOrFail',
                'deleteOrFail',
                'forceDelete'
            ])
            ->disableOriginalConstructor()
            ->getMock();

        $this->craftInventoryItemServiceMock = $this
            ->getMockBuilder(CraftInventoryItemService::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['createCellsInItemsForColumn'])
            ->getMock();

        $this->craftInventoryItemCellServiceMock = $this
            ->getMockBuilder(CraftInventoryItemCellService::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['updateCellValue'])
            ->getMock();
    }

    private function getService(): CraftsInventoryColumnService
    {
        return new CraftsInventoryColumnService(
            $this->craftsInventoryColumnRepositoryMock,
            $this->craftInventoryItemServiceMock,
            $this->craftInventoryItemCellServiceMock
        );
    }

    /**
     * @throws Exception
     */
    public function testGetAllOrdered(): void
    {
        $collectionStub = $this->createStub(Collection::class);
        $this->craftsInventoryColumnRepositoryMock->expects(self::once())
            ->method('getAllOrdered')
            ->willReturn($collectionStub);

        self::assertSame(
            $collectionStub,
            $this->getService()->getAllOrdered()
        );
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function createTestsDataProvider(): array
    {
        $name = 'Name';
        $type = CraftsInventoryColumnTypeEnum::TEXT;
        $defaultOption = '';
        $typeOptions = [];
        $background_color = '';

        return [
            'test create data' => [
                $name,
                $type,
                $defaultOption,
                $typeOptions,
                $background_color,
                [
                    'name' => $name,
                    'type' => $type,
                    'type_options' => $typeOptions,
                    'background_color' => $background_color
                ]
            ]
        ];
    }

    /**
     * @dataProvider createTestsDataProvider
     * @throws Throwable
     */
    public function testCreateSuccess(
        string $expectedName,
        CraftsInventoryColumnTypeEnum $expectedType,
        string $expectedDefaultOption,
        array $expectedTypeOptions,
        string $expectedBackgroundColor,
        array $expectedNewModelInstanceArgs
    ): void {
        $craftsInventoryColumnMock = $this->getMockBuilder(CraftsInventoryColumn::class)
            ->onlyMethods([])
            ->disableOriginalConstructor()
            ->getMock();

        $this->craftsInventoryColumnRepositoryMock->expects(self::once())
            ->method('getNewModelInstance')
            ->with($expectedNewModelInstanceArgs)
            ->willReturn($craftsInventoryColumnMock);

        $this->craftsInventoryColumnRepositoryMock->expects(self::once())
            ->method('saveOrFail')
            ->with($craftsInventoryColumnMock)
            ->willReturn($craftsInventoryColumnMock);

        $this->craftInventoryItemServiceMock->expects(self::once())
            ->method('createCellsInItemsForColumn')
            ->with($craftsInventoryColumnMock, $expectedDefaultOption);

        self::assertInstanceOf(
            CraftsInventoryColumn::class,
            $this->getService()->create(
                $expectedName,
                $expectedType,
                $expectedDefaultOption,
                $expectedTypeOptions,
                $expectedBackgroundColor
            )
        );
    }

    /**
     * @dataProvider createTestsDataProvider
     * @throws Throwable
     */
    public function testCreateFailsOnColumnSave(
        string $expectedName,
        CraftsInventoryColumnTypeEnum $expectedType,
        string $expectedDefaultOption,
        array $expectedTypeOptions,
        string $expectedBackgroundColor,
        array $expectedNewModelInstanceArgs
    ): void {
        $craftsInventoryColumnMock = $this->getMockBuilder(CraftsInventoryColumn::class)
            ->onlyMethods([])
            ->disableOriginalConstructor()
            ->getMock();

        $this->craftsInventoryColumnRepositoryMock->expects(self::once())
            ->method('getNewModelInstance')
            ->with($expectedNewModelInstanceArgs)
            ->willReturn($craftsInventoryColumnMock);

        $this->craftsInventoryColumnRepositoryMock->expects(self::once())
            ->method('saveOrFail')
            ->with($craftsInventoryColumnMock)
            ->willThrowException($expectedException = new Exception('Test'));

        try {
            $this->getService()->create(
                $expectedName,
                $expectedType,
                $expectedDefaultOption,
                $expectedTypeOptions,
                $expectedBackgroundColor
            );
        } catch (Throwable $t) {
            self::assertSame(
                $expectedException,
                $t
            );
        }
    }

    /**
     * @dataProvider createTestsDataProvider
     * @throws Throwable
     */
    public function testCreateFailsOnCreateCellsInItemsForColumn(
        string $expectedName,
        CraftsInventoryColumnTypeEnum $expectedType,
        string $expectedDefaultOption,
        array $expectedTypeOptions,
        string $expectedBackgroundColor,
        array $expectedNewModelInstanceArgs
    ): void {
        $craftsInventoryColumnMock = $this->getMockBuilder(CraftsInventoryColumn::class)
            ->onlyMethods([])
            ->disableOriginalConstructor()
            ->getMock();

        $this->craftsInventoryColumnRepositoryMock->expects(self::once())
            ->method('getNewModelInstance')
            ->with($expectedNewModelInstanceArgs)
            ->willReturn($craftsInventoryColumnMock);

        $this->craftsInventoryColumnRepositoryMock->expects(self::once())
            ->method('saveOrFail')
            ->with($craftsInventoryColumnMock)
            ->willReturn($craftsInventoryColumnMock);

        $this->craftInventoryItemServiceMock->expects(self::once())
            ->method('createCellsInItemsForColumn')
            ->with($craftsInventoryColumnMock, $expectedDefaultOption)
            ->willThrowException($expectedException = new Exception('Test'));

        $this->craftsInventoryColumnRepositoryMock->expects(self::once())
            ->method('deleteOrFail')
            ->with($craftsInventoryColumnMock)
            ->willReturn(true);

        try {
            $this->getService()->create(
                $expectedName,
                $expectedType,
                $expectedDefaultOption,
                $expectedTypeOptions,
                $expectedBackgroundColor
            );
        } catch (Throwable $t) {
            self::assertSame(
                $expectedException,
                $t
            );
        }
    }

    /**
     * @dataProvider createTestsDataProvider
     * @throws Throwable
     */
    public function testCreateFailsAfterFailingOnCreateCellsInItemsForColumn(
        string $expectedName,
        CraftsInventoryColumnTypeEnum $expectedType,
        string $expectedDefaultOption,
        array $expectedTypeOptions,
        string $expectedBackgroundColor,
        array $expectedNewModelInstanceArgs
    ): void {
        $craftsInventoryColumnMock = $this->getMockBuilder(CraftsInventoryColumn::class)
            ->onlyMethods([])
            ->disableOriginalConstructor()
            ->getMock();

        $this->craftsInventoryColumnRepositoryMock->expects(self::once())
            ->method('getNewModelInstance')
            ->with($expectedNewModelInstanceArgs)
            ->willReturn($craftsInventoryColumnMock);

        $this->craftsInventoryColumnRepositoryMock->expects(self::once())
            ->method('saveOrFail')
            ->with($craftsInventoryColumnMock)
            ->willReturn($craftsInventoryColumnMock);

        $this->craftInventoryItemServiceMock->expects(self::once())
            ->method('createCellsInItemsForColumn')
            ->with($craftsInventoryColumnMock, $expectedDefaultOption)
            ->willThrowException($expectedException = new Exception('Test'));

        $this->craftsInventoryColumnRepositoryMock->expects(self::once())
            ->method('deleteOrFail')
            ->with($craftsInventoryColumnMock)
            ->willThrowException($expectedException);

        try {
            $this->getService()->create(
                $expectedName,
                $expectedType,
                $expectedDefaultOption,
                $expectedTypeOptions,
                $expectedBackgroundColor
            );
        } catch (Throwable $t) {
            self::assertSame(
                $expectedException,
                $t
            );
        }
    }

    /**
     * @throws Throwable
     */
    public function testDuplicateSuccess(): void
    {
        $craftsInventoryColumnMock = $this->getMockBuilder(CraftsInventoryColumn::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->craftsInventoryColumnRepositoryMock->expects(self::once())
            ->method('find')
            ->with(1)
            ->willReturn($craftsInventoryColumnMock);

        $this->craftsInventoryColumnRepositoryMock->expects(self::once())
            ->method('replicate')
            ->with($craftsInventoryColumnMock)
            ->willReturn($craftsInventoryColumnMock);

        $this->craftsInventoryColumnRepositoryMock->expects(self::once())
            ->method('saveOrFail')
            ->with($craftsInventoryColumnMock)
            ->willReturn($craftsInventoryColumnMock);

        $this->craftInventoryItemServiceMock->expects(self::once())
            ->method('createCellsInItemsForColumn')
            ->with($craftsInventoryColumnMock);

        self::assertSame(
            $craftsInventoryColumnMock,
            $this->getService()->duplicate(1)
        );
    }

    /**
     * @throws Throwable
     */
    public function testDuplicateFailsOnSaveOrFail(): void
    {
        $craftsInventoryColumnMock = $this->getMockBuilder(CraftsInventoryColumn::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->craftsInventoryColumnRepositoryMock->expects(self::once())
            ->method('find')
            ->with(1)
            ->willReturn($craftsInventoryColumnMock);

        $this->craftsInventoryColumnRepositoryMock->expects(self::once())
            ->method('replicate')
            ->with($craftsInventoryColumnMock)
            ->willReturn($craftsInventoryColumnMock);

        $this->craftsInventoryColumnRepositoryMock->expects(self::once())
            ->method('saveOrFail')
            ->with($craftsInventoryColumnMock)
            ->willThrowException($expectedException = new Exception('Test'));

        try {
            $this->getService()->duplicate(1);
        } catch (Throwable $t) {
            self::assertSame(
                $expectedException,
                $t
            );
        }
    }

    /**
     * @throws Throwable
     */
    public function testDuplicateFailsOnCreateCellsInItemsForColumn(): void
    {
        $craftsInventoryColumnMock = $this->getMockBuilder(CraftsInventoryColumn::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->craftsInventoryColumnRepositoryMock->expects(self::once())
            ->method('find')
            ->with(1)
            ->willReturn($craftsInventoryColumnMock);

        $this->craftsInventoryColumnRepositoryMock->expects(self::once())
            ->method('replicate')
            ->with($craftsInventoryColumnMock)
            ->willReturn($craftsInventoryColumnMock);

        $this->craftsInventoryColumnRepositoryMock->expects(self::once())
            ->method('saveOrFail')
            ->with($craftsInventoryColumnMock)
            ->willReturn($craftsInventoryColumnMock);

        $this->craftInventoryItemServiceMock->expects(self::once())
            ->method('createCellsInItemsForColumn')
            ->with($craftsInventoryColumnMock)
            ->willThrowException($expectedException = new Exception('Test'));

        try {
            $this->getService()->duplicate(1);
        } catch (Throwable $t) {
            self::assertSame(
                $expectedException,
                $t
            );
        }
    }

    /**
     * @throws Throwable
     */
    public function testUpdateNameSuccess(): void
    {
        $expectedName = 'Test';
        $expectedArgs = [
            'name' => $expectedName
        ];

        $craftsInventoryColumnMock = $this->getMockBuilder(CraftsInventoryColumn::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->craftsInventoryColumnRepositoryMock->expects(self::once())
            ->method('updateOrFail')
            ->with($craftsInventoryColumnMock, $expectedArgs)
            ->willReturn($craftsInventoryColumnMock);

        $this->getService()->updateName($expectedName, $craftsInventoryColumnMock);
    }

    /**
     * @throws Throwable
     */
    public function testUpdateNameFailsOnUpdateOrFail(): void
    {
        $expectedName = 'Test';
        $expectedArgs = [
            'name' => $expectedName
        ];

        $craftsInventoryColumnMock = $this->getMockBuilder(CraftsInventoryColumn::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->craftsInventoryColumnRepositoryMock->expects(self::once())
            ->method('updateOrFail')
            ->with($craftsInventoryColumnMock, $expectedArgs)
            ->willThrowException($expectedException = new Exception('Test'));

        try {
            $this->getService()->updateName($expectedName, $craftsInventoryColumnMock);
        } catch (Throwable $t) {
            self::assertSame(
                $expectedException,
                $t
            );
        }
    }

    /**
     * @throws Throwable
     */
    public function testUpdateBackgroundColorSuccess(): void
    {
        $expectedBackgroundColor = 'Test';
        $expectedArgs = [
            'background_color' => $expectedBackgroundColor
        ];

        $craftsInventoryColumnMock = $this->getMockBuilder(CraftsInventoryColumn::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->craftsInventoryColumnRepositoryMock->expects(self::once())
            ->method('updateOrFail')
            ->with($craftsInventoryColumnMock, $expectedArgs)
            ->willReturn($craftsInventoryColumnMock);

        $this->getService()->updateBackgroundColor($expectedBackgroundColor, $craftsInventoryColumnMock);
    }

    /**
     * @throws Throwable
     */
    public function testUpdateBackgroundColorFailsOnUpdateOrFail(): void
    {
        $expectedBackgroundColor = 'Test';
        $expectedArgs = [
            'background_color' => $expectedBackgroundColor
        ];

        $craftsInventoryColumnMock = $this->getMockBuilder(CraftsInventoryColumn::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->craftsInventoryColumnRepositoryMock->expects(self::once())
            ->method('updateOrFail')
            ->with($craftsInventoryColumnMock, $expectedArgs)
            ->willThrowException($expectedException = new Exception('Test'));

        try {
            $this->getService()->updateBackgroundColor($expectedBackgroundColor, $craftsInventoryColumnMock);
        } catch (Throwable $t) {
            self::assertSame(
                $expectedException,
                $t
            );
        }
    }

    /**
     * @throws Throwable
     */
    public function testUpdateTypeOptions(): void
    {
        $craftsInventoryColumnMock = $this->getMockBuilder(CraftsInventoryColumn::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getAttribute'])
            ->getMock();

        $craftInventoryItemCellMock = $this->getMockBuilder(CraftInventoryItemCell::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getAttribute', 'setAttribute'])
            ->getMock();

        $craftsInventoryColumnMock->expects(self::exactly(2))
            ->method('getAttribute')
            ->with('type_options')
            ->willReturnOnConsecutiveCalls(['abc'], []);

        $this->craftsInventoryColumnRepositoryMock->expects(self::once())
            ->method('updateOrFail')
            ->with(
                $craftsInventoryColumnMock,
                [
                    'type_options' => []
                ]
            )->willReturn($craftsInventoryColumnMock);

        $this->craftsInventoryColumnRepositoryMock->expects(self::once())
            ->method('getAllItemCells')
            ->willReturn(
                Collection::make([
                    $craftInventoryItemCellMock,
                    $craftInventoryItemCellMock,
                    $craftInventoryItemCellMock
                ])
            );

        $craftInventoryItemCellMock->expects(self::exactly(3))
            ->method('getAttribute')
            ->with('cell_value')
            ->willReturn('abc');

        $this->craftInventoryItemCellServiceMock->expects(self::exactly(3))
            ->method('updateCellValue')
            ->with('', $craftInventoryItemCellMock);

        $this->getService()->updateTypeOptions(
            [],
            $craftsInventoryColumnMock
        );
    }

    public function testupdateTypeOptionsFailsOnUpdateOrFail(): void
    {
        $craftsInventoryColumnMock = $this->getMockBuilder(CraftsInventoryColumn::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getAttribute'])
            ->getMock();

        $craftsInventoryColumnMock->expects(self::once())
            ->method('getAttribute')
            ->with('type_options')
            ->willReturnOnConsecutiveCalls(['abc'], []);

        $this->craftsInventoryColumnRepositoryMock->expects(self::once())
            ->method('updateOrFail')
            ->with(
                $craftsInventoryColumnMock,
                [
                    'type_options' => []
                ]
            )->willThrowException($expectedException = new Exception('Test'));

        try {
            $this->getService()->updateTypeOptions(
                [],
                $craftsInventoryColumnMock
            );
        } catch (Throwable $t) {
            self::assertSame($expectedException, $t);
        }
    }

    public function testupdateTypeOptionsFailsOnUpdateCellValue(): void
    {
        $craftsInventoryColumnMock = $this->getMockBuilder(CraftsInventoryColumn::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getAttribute'])
            ->getMock();

        $craftInventoryItemCellMock = $this->getMockBuilder(CraftInventoryItemCell::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getAttribute', 'setAttribute'])
            ->getMock();

        $craftsInventoryColumnMock->expects(self::exactly(2))
            ->method('getAttribute')
            ->with('type_options')
            ->willReturnOnConsecutiveCalls(['abc'], []);

        $this->craftsInventoryColumnRepositoryMock->expects(self::once())
            ->method('updateOrFail')
            ->with(
                $craftsInventoryColumnMock,
                [
                    'type_options' => []
                ]
            )->willReturn($craftsInventoryColumnMock);

        $this->craftsInventoryColumnRepositoryMock->expects(self::once())
            ->method('getAllItemCells')
            ->willReturn(
                Collection::make([
                    $craftInventoryItemCellMock,
                    $craftInventoryItemCellMock,
                    $craftInventoryItemCellMock
                ])
            );

        $craftInventoryItemCellMock->expects(self::once())
            ->method('getAttribute')
            ->with('cell_value')
            ->willReturn('abc');

        $this->craftInventoryItemCellServiceMock->expects(self::once())
            ->method('updateCellValue')
            ->with('', $craftInventoryItemCellMock)
            ->willThrowException($expectedException = new Exception('Test'));

        try {
            $this->getService()->updateTypeOptions(
                [],
                $craftsInventoryColumnMock
            );
        } catch (Throwable $t) {
            self::assertSame($expectedException, $t);
        }
    }

    public function testForceDeleteWithInventoryModel(): void
    {
        $craftsInventoryColumnMock = $this->getMockBuilder(CraftsInventoryColumn::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->craftsInventoryColumnRepositoryMock->expects(self::exactly(2))
            ->method('forceDelete')
            ->with($craftsInventoryColumnMock)
            ->willReturnOnConsecutiveCalls(true, false);

        self::assertTrue($this->getService()->forceDelete($craftsInventoryColumnMock));
        self::assertFalse($this->getService()->forceDelete($craftsInventoryColumnMock));
    }

    public function testForceDeleteWithInt(): void
    {
        $craftsInventoryColumnMock = $this->getMockBuilder(CraftsInventoryColumn::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->craftsInventoryColumnRepositoryMock->expects(self::exactly(4))
            ->method('forceDelete')
            ->with($craftsInventoryColumnMock)
            ->willReturnOnConsecutiveCalls(true, false, true, false);

        $firstId = 1;
        $secondId = 2;
        $this->craftsInventoryColumnRepositoryMock->expects($matcher = self::exactly(4))
            ->method('find')
            ->willReturnCallback(
                function (int $id) use (
                    $matcher,
                    $firstId,
                    $secondId,
                    $craftsInventoryColumnMock
                ): CraftsInventoryColumn {
                    switch ($matcher->numberOfInvocations()) {
                        case 1:
                        case 2:
                            self::assertSame($firstId, $id);
                            return $craftsInventoryColumnMock;
                        case 3:
                        case 4:
                            self::assertSame($secondId, $id);
                            return $craftsInventoryColumnMock;
                        default:
                            throw new AssertionError('Number of invocations not expected.');
                    }
                }
            );

        self::assertTrue($this->getService()->forceDelete($firstId));
        self::assertFalse($this->getService()->forceDelete($firstId));
        self::assertTrue($this->getService()->forceDelete($secondId));
        self::assertFalse($this->getService()->forceDelete($secondId));
    }
}
