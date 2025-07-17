<?php

namespace Tests\Unit\Artwork\Modules\InventoryManagement\Exports;

use Artwork\Modules\Inventory\Exports\InventoryManagementExport;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\View\Factory;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;

class InventoryManagementExportTest extends TestCase
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public static function viewTestDataProvider(): array
    {
        $columns = Collection::make();
        $crafts = SupportCollection::make();
        return [
            'test view' => [
                'exports.inventoryManagement',
                $columns,
                $crafts
            ]
        ];
    }

    /** @dataProvider viewTestDataProvider */
    public function testView(
        string $expectedView,
        Collection $expectedColumns,
        SupportCollection $expectedCrafts
    ): void {
        $viewFactoryMock = $this->getMockBuilder(Factory::class)
            ->onlyMethods(['make'])
            ->disableOriginalConstructor()
            ->getMock();

        $viewMock = $this->getMockBuilder(View::class)
            ->disableOriginalConstructor()
            ->getMock();

        $viewFactoryMock->expects(self::once())
            ->method('make')
            ->with(
                $expectedView,
                [
                    'columns' => $expectedColumns,
                    'crafts' => $expectedCrafts
                ]
            )->willReturn($viewMock);

        $inventoryManagementExport = new InventoryManagementExport($viewFactoryMock);
        $inventoryManagementExport->setColumns($expectedColumns);
        $inventoryManagementExport->setCrafts($expectedCrafts);

        $inventoryManagementExport->view();
    }

    /**
     * @return array<string, array<int, mixed>>
     * @throws Exception
     */
    public static function gettersAndSettersTestDataProvider(): array
    {
        return [
            'test getters and setters' => [
                SupportCollection::make(['abc', 'def', 'hij']),
                new InventoryManagementExport(self::createStub(Factory::class)),
            ]
        ];
    }

    /**
     * @dataProvider gettersAndSettersTestDataProvider
     */
    public function testGettersAndSetters(
        SupportCollection $expectedCollection,
        InventoryManagementExport $inventoryManagementExport
    ): void {
        //assert returns self instance
        self::assertSame($inventoryManagementExport, $inventoryManagementExport->setColumns($expectedCollection));
        self::assertSame($inventoryManagementExport, $inventoryManagementExport->setCrafts($expectedCollection));

        //assert state is expected
        self::assertSame($expectedCollection, $inventoryManagementExport->getColumns());
        self::assertSame($expectedCollection, $inventoryManagementExport->getCrafts());
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function stylesTestDataProvider(): array
    {
        return [
            'test styles' => [
                [
                    1 => ['font' => ['bold' => true]]
                ]
            ]
        ];
    }

    /**
     * @dataProvider stylesTestDataProvider
     */
    public function testStyles(
        array $expectedStyles
    ): void {
        $styles = (new InventoryManagementExport(
            $this->getMockBuilder(Factory::class)
                ->disableOriginalConstructor()
                ->getMock()
        ))->styles(
            $this->getMockBuilder(Worksheet::class)
                ->disableOriginalConstructor()
                ->getMock()
        );

        self::assertIsArray($styles);
        self::assertSame($expectedStyles, $styles);
    }
}
