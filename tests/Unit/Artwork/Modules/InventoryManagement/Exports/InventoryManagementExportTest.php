<?php

namespace Tests\Unit\Artwork\Modules\InventoryManagement\Exports;

use Artwork\Modules\InventoryManagement\Exports\InventoryManagementExport;
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
     * @throws Exception
     */
    public function testGettersAndSetters(): void
    {
        $expectedCollection = SupportCollection::make(['abc', 'def', 'hij']);
        $inventoryManagementExport = new InventoryManagementExport($this->createStub(Factory::class));

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
        $columns = Collection::make();
        $crafts = SupportCollection::make();
        return [
            'test styles' => [
                $columns,
                $crafts,
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
        Collection $columns,
        SupportCollection $crafts,
        array $expectedStyles
    ): void {
        $styles = (new InventoryManagementExport(
            $this->getMockBuilder(Factory::class)
                ->disableOriginalConstructor()
                ->getMock(),
            $columns,
            $crafts
        ))->styles(
            $this->getMockBuilder(Worksheet::class)
                ->disableOriginalConstructor()
                ->getMock()
        );

        self::assertIsArray($styles);
        self::assertSame($expectedStyles, $styles);
    }
}
