<?php

declare(strict_types=1);

namespace Tests\Unit;

use Artwork\Core\Excel\SafeValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class SafeValueBinderTest extends TestCase
{
    /**
     * @return array<string, array{mixed, string}>
     */
    public static function valuesProvider(): array
    {
        return [
            'hyperlink formula' => ['=HYPERLINK("x")', DataType::TYPE_STRING],
            'negative number' => ['-5', DataType::TYPE_NUMERIC],
            'phone number with plus' => ['+49 170', DataType::TYPE_STRING],
            'plain text' => ['Normal', DataType::TYPE_STRING],
            'decimal number' => ['12.5', DataType::TYPE_NUMERIC],
            'dde command' => ["=cmd|' /C calc'!A0", DataType::TYPE_STRING],
            'leading spaces before formula' => ['  =1+1', DataType::TYPE_STRING],
            'at prefix' => ['@SUM(A1)', DataType::TYPE_STRING],
            'tab prefix' => ["\t=1+1", DataType::TYPE_STRING],
            'carriage return prefix' => ["\r=1+1", DataType::TYPE_STRING],
            'native int' => [42, DataType::TYPE_NUMERIC],
            'native float' => [-3.25, DataType::TYPE_NUMERIC],
            'empty string' => ['', DataType::TYPE_STRING],
            'null' => [null, DataType::TYPE_NULL],
        ];
    }

    #[DataProvider('valuesProvider')]
    public function testBindsValueWithExpectedDataType(mixed $value, string $expectedType): void
    {
        $cell = $this->makeCell();

        self::assertTrue((new SafeValueBinder())->bindValue($cell, $value));
        self::assertSame($expectedType, $cell->getDataType());
    }

    public function testFormulaLikeStringsKeepTheirRawContent(): void
    {
        $cell = $this->makeCell();
        (new SafeValueBinder())->bindValue($cell, '=HYPERLINK("x")');

        self::assertSame('=HYPERLINK("x")', $cell->getValue());
        self::assertNotSame(DataType::TYPE_FORMULA, $cell->getDataType());
    }

    public function testNumericStringsStayNumeric(): void
    {
        $cell = $this->makeCell();
        (new SafeValueBinder())->bindValue($cell, '-5');

        self::assertSame(DataType::TYPE_NUMERIC, $cell->getDataType());
        self::assertEquals(-5, $cell->getValue());
    }

    private function makeCell(): Cell
    {
        return (new Spreadsheet())->getActiveSheet()->getCell('A1');
    }
}
