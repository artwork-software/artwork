<?php

namespace Tests\Unit\Artwork\Modules\InventoryManagement\Services;

use Artwork\Modules\InventoryManagement\Models\CraftInventoryItemCell;
use Artwork\Modules\InventoryManagement\Repositories\CraftInventoryItemCellRepository;
use Artwork\Modules\InventoryManagement\Services\CraftInventoryItemCellService;
use AssertionError;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use PHPUnit\Framework\TestCase;
use Throwable;

class CraftInventoryItemCellServiceTest extends TestCase
{
    private readonly CraftInventoryItemCellRepository $craftInventoryItemCellRepositoryMock;

    private readonly CraftInventoryItemCell $craftInventoryItemCellMock;

    protected function setUp(): void
    {
        $this->craftInventoryItemCellRepositoryMock = $this->getMockBuilder(CraftInventoryItemCellRepository::class)
            ->onlyMethods(['getNewModelInstance', 'saveOrFail', 'updateOrFail'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->craftInventoryItemCellMock = $this->getMockBuilder(CraftInventoryItemCell::class)
            ->onlyMethods(['getAttribute'])
            ->disableOriginalConstructor()
            ->getMock();
    }

    private function getService(): CraftInventoryItemCellService
    {
        return new CraftInventoryItemCellService($this->craftInventoryItemCellRepositoryMock);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function createTestDataProvider(): array
    {
        $columnId = 1;
        $itemId = 1;
        $cellValue = 'Test';
        return [
            'test create' => [
                $columnId,
                $itemId,
                $cellValue,
                [
                    'crafts_inventory_column_id' => $columnId,
                    'craft_inventory_item_id' => $itemId,
                    'cell_value' => $cellValue
                ]
            ]
        ];
    }

    /**
     * @dataProvider createTestDataProvider
     * @throws Throwable
     */
    public function testCreate(
        int $expectedColumnId,
        int $expectedItemId,
        string $expectedCellValue,
        array $expectedNewModelInstanceArgs
    ): void {
        $this->craftInventoryItemCellRepositoryMock->expects(self::once())
            ->method('getNewModelInstance')
            ->with($expectedNewModelInstanceArgs)
            ->willReturn($this->craftInventoryItemCellMock);

        $this->craftInventoryItemCellRepositoryMock->expects(self::once())
            ->method('saveOrFail')
            ->with($this->craftInventoryItemCellMock)
            ->willReturn($this->craftInventoryItemCellMock);

        self::assertInstanceOf(
            CraftInventoryItemCell::class,
            $this->getService()->create(
                $expectedColumnId,
                $expectedItemId,
                $expectedCellValue
            )
        );
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function createExceptionTestDataProvider(): array
    {
        $columnId = 1;
        $itemId = 1;
        $cellValue = 'Test';
        return [
            'test create' => [
                $columnId,
                $itemId,
                $cellValue,
                [
                    'crafts_inventory_column_id' => $columnId,
                    'craft_inventory_item_id' => $itemId,
                    'cell_value' => $cellValue
                ],
                new Exception('Test')
            ]
        ];
    }

    /**
     * @dataProvider createExceptionTestDataProvider
     * @throws Throwable
     */
    public function testCreateException(
        int $expectedColumnId,
        int $expectedItemId,
        string $expectedCellValue,
        array $expectedNewModelInstanceArgs,
        Exception $expectedException
    ): void {
        $this->craftInventoryItemCellRepositoryMock->expects(self::once())
            ->method('getNewModelInstance')
            ->with($expectedNewModelInstanceArgs)
            ->willReturn($this->craftInventoryItemCellMock);

        $this->craftInventoryItemCellRepositoryMock->expects(self::once())
            ->method('saveOrFail')
            ->with($this->craftInventoryItemCellMock)
            ->willThrowException($expectedException);

        try {
            $this->getService()->create(
                $expectedColumnId,
                $expectedItemId,
                $expectedCellValue
            );
        } catch (Throwable $t) {
            self::assertSame($expectedException, $t);
        }
    }

    /**
     * @throws Throwable
     */
    public function testUpdateCellValue(): void
    {
        $this->craftInventoryItemCellRepositoryMock->expects(self::once())
            ->method('updateOrFail')
            ->with($this->craftInventoryItemCellMock, ['cell_value' => 'Test'])
            ->willReturn($this->craftInventoryItemCellMock);

        $this->getService()->updateCellValue('Test', $this->craftInventoryItemCellMock);
    }

    public function testUpdateCellValueException(): void
    {
        $expectedException = new Exception('Test');

        $this->craftInventoryItemCellRepositoryMock->expects(self::once())
            ->method('updateOrFail')
            ->with($this->craftInventoryItemCellMock, ['cell_value' => 'Test'])
            ->willThrowException($expectedException);

        try {
            $this->getService()->updateCellValue('Test', $this->craftInventoryItemCellMock);
        } catch (Throwable $t) {
            self::assertSame($expectedException, $t);
        }
    }

    public function testGetNameForSchedulingFromCellsWithEmptyCollection(): void
    {
        $collectionMock = $this->getMockBuilder(Collection::class)
            ->onlyMethods(['first'])
            ->disableOriginalConstructor()
            ->getMock();

        $collectionMock->expects(self::once())
            ->method('first')
            ->with(
                function (CraftInventoryItemCell $cell): bool {
                    return is_string($cell->getAttribute('cell_value'));
                }
            )->willReturn(null);

        self::assertSame(
            '',
            $this->getService()->getNameForSchedulingFromCells($collectionMock)
        );
    }

    public function testGetNameForSchedulingFromCellsCallsFirstCallback(): void
    {
        $expectedCellValue = 'Test';
        $collectionMock = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['first'])
            ->getMock();

        $collectionMock->expects(self::once())
            ->method('first')
            ->with(
                function (CraftInventoryItemCell $cell): bool {
                    return is_numeric($cell->getAttribute('cell_value'));
                }
            )
            ->willReturn($this->craftInventoryItemCellMock);

        $this->craftInventoryItemCellMock->expects(self::once())
            ->method('getAttribute')
            ->with('cell_value')
            ->willReturn($expectedCellValue);

        $result = $this->getService()->getNameForSchedulingFromCells($collectionMock);
        self::assertSame($expectedCellValue, $result);
    }

    public function testGetNameForSchedulingFromCellsCallsGetAttributeTwoTimes(): void
    {
        $expectedCellValue = 'Test';
        $collection = Collection::make([$this->craftInventoryItemCellMock]);

        $this->craftInventoryItemCellMock->expects($matcher = self::exactly(2))
            ->method('getAttribute')
            ->willReturnCallback(
                function (string $key) use ($matcher, $expectedCellValue): string {
                    switch ($matcher->numberOfInvocations()) {
                        case 1:
                        case 2:
                            self::assertSame('cell_value', $key);
                            return $expectedCellValue;
                        default:
                            throw new AssertionError('Number of invocations not expected.');
                    }
                }
            );

        self::assertSame(
            $expectedCellValue,
            $this->getService()->getNameForSchedulingFromCells($collection)
        );
    }

    public function testGetItemCountForSchedulingFromCellsWithEmptyCollection(): void
    {
        $collectionMock = $this->getMockBuilder(Collection::class)
            ->onlyMethods(['first'])
            ->disableOriginalConstructor()
            ->getMock();

        $collectionMock->expects(self::once())
            ->method('first')
            ->with(
                function (CraftInventoryItemCell $cell): bool {
                    return is_string($cell->getAttribute('cell_value'));
                }
            )->willReturn(null);

        self::assertSame(
            0,
            $this->getService()->getItemCountForSchedulingFromCells($collectionMock)
        );
    }

    public function testGetItemCountForSchedulingFromCellsCallsFirstCallback(): void
    {
        $expectedCellValue = 3;
        $collectionMock = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['first'])
            ->getMock();

        $collectionMock->expects(self::once())
            ->method('first')
            ->with(
                function (CraftInventoryItemCell $cell): bool {
                    return is_numeric($cell->getAttribute('cell_value'));
                }
            )
            ->willReturn($this->craftInventoryItemCellMock);

        $this->craftInventoryItemCellMock->expects(self::once())
            ->method('getAttribute')
            ->with('cell_value')
            ->willReturn($expectedCellValue);

        self::assertSame(
            $expectedCellValue,
            $this->getService()->getItemCountForSchedulingFromCells($collectionMock)
        );
    }

    public function testGetItemCountForSchedulingFromCellsCallsGetAttributeTwoTimes(): void
    {
        $expectedCellValue = 3;
        $collection = Collection::make([$this->craftInventoryItemCellMock]);

        $this->craftInventoryItemCellMock->expects($matcher = self::exactly(2))
            ->method('getAttribute')
            ->willReturnCallback(
                function (string $key) use ($matcher, $expectedCellValue): int {
                    switch ($matcher->numberOfInvocations()) {
                        case 1:
                        case 2:
                            self::assertSame('cell_value', $key);
                            return $expectedCellValue;
                        default:
                            throw new AssertionError('Number of invocations not expected.');
                    }
                }
            );

        self::assertSame(
            $expectedCellValue,
            $this->getService()->getItemCountForSchedulingFromCells($collection)
        );
    }
}
