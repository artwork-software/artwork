<?php

namespace Tests\Unit\Artwork\Modules\InventoryManagement\Enums;

use Artwork\Modules\Inventory\Enums\CraftsInventoryColumnTypeEnum;
use PHPUnit\Framework\TestCase;

class CraftsInventoryColumnTypeEnumTest extends TestCase
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public static function enumTestDataProvider(): array
    {
        return [
            'test enums' => [
                [
                    'TEXT',
                    'DATE',
                    'CHECKBOX',
                    'SELECT'
                ],
                0,
                1,
                2,
                3
            ]
        ];
    }

    /** @dataProvider enumTestDataProvider */
    public function testEnums(
        array $expectedNames,
        int $expectedTextValue,
        int $expectedDateValue,
        int $expectedCheckboxValue,
        int $expectedSelectValue
    ): void {
        foreach (CraftsInventoryColumnTypeEnum::cases() as $craftsInventoryColumnTypeEnum) {
            self::assertTrue(in_array($craftsInventoryColumnTypeEnum->name, $expectedNames, true));
        }

        self::assertSame(CraftsInventoryColumnTypeEnum::TEXT->value, $expectedTextValue);
        self::assertSame(CraftsInventoryColumnTypeEnum::DATE->value, $expectedDateValue);
        self::assertSame(CraftsInventoryColumnTypeEnum::CHECKBOX->value, $expectedCheckboxValue);
        self::assertSame(CraftsInventoryColumnTypeEnum::SELECT->value, $expectedSelectValue);
    }
}
