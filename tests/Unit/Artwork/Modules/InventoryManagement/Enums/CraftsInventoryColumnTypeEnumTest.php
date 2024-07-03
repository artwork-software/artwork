<?php

namespace Tests\Unit\Artwork\Modules\InventoryManagement\Enums;

use Artwork\Modules\InventoryManagement\Enums\CraftsInventoryColumnTypeEnum;
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
                ]
            ]
        ];
    }

    /** @dataProvider enumTestDataProvider */
    public function testEnums(array $expectedCases): void
    {
        foreach (CraftsInventoryColumnTypeEnum::cases() as $craftsInventoryColumnTypeEnum) {
            self::assertTrue(in_array($craftsInventoryColumnTypeEnum->name, $expectedCases, true));
        }

        self::assertSame(CraftsInventoryColumnTypeEnum::TEXT->value, 0);
        self::assertSame(CraftsInventoryColumnTypeEnum::DATE->value, 1);
        self::assertSame(CraftsInventoryColumnTypeEnum::CHECKBOX->value, 2);
        self::assertSame(CraftsInventoryColumnTypeEnum::SELECT->value, 3);
    }
}
