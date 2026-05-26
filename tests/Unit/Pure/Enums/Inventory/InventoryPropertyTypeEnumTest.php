<?php

namespace Tests\Unit\Pure\Enums\Inventory;

use Artwork\Modules\Inventory\Enums\InventoryPropertyTypeEnum;
use PHPUnit\Framework\Attributes\Test;
use Tests\Unit\UnitTestCase;

final class InventoryPropertyTypeEnumTest extends UnitTestCase
{
    #[Test]
    public function it_has_text_case(): void
    {
        $this->assertSame('text', InventoryPropertyTypeEnum::TEXT->value);
    }

    #[Test]
    public function it_has_date_case(): void
    {
        $this->assertSame('date', InventoryPropertyTypeEnum::DATE->value);
    }

    #[Test]
    public function it_has_checkbox_case(): void
    {
        $this->assertSame('checkbox', InventoryPropertyTypeEnum::CHECKBOX->value);
    }

    #[Test]
    public function it_has_select_case(): void
    {
        $this->assertSame('select', InventoryPropertyTypeEnum::SELECT->value);
    }

    #[Test]
    public function it_has_number_case(): void
    {
        $this->assertSame('number', InventoryPropertyTypeEnum::NUMBER->value);
    }

    #[Test]
    public function it_has_file_case(): void
    {
        $this->assertSame('file', InventoryPropertyTypeEnum::FILE->value);
    }

    #[Test]
    public function it_has_six_cases(): void
    {
        $this->assertCount(6, InventoryPropertyTypeEnum::cases());
    }

    #[Test]
    public function try_from_returns_null_for_unknown(): void
    {
        $this->assertNull(InventoryPropertyTypeEnum::tryFrom('boolean'));
    }
}
