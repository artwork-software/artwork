<?php

namespace Tests\Unit\Pure\Enums\Inventory;

use Artwork\Modules\Inventory\Enums\CraftsInventoryColumnTypeEnum;
use PHPUnit\Framework\Attributes\Test;
use Tests\Unit\UnitTestCase;

final class CraftsInventoryColumnTypeEnumTest extends UnitTestCase
{
    #[Test]
    public function text_case_is_zero(): void
    {
        $this->assertSame(0, CraftsInventoryColumnTypeEnum::TEXT->value);
    }

    #[Test]
    public function date_case_is_one(): void
    {
        $this->assertSame(1, CraftsInventoryColumnTypeEnum::DATE->value);
    }

    #[Test]
    public function checkbox_case_is_two(): void
    {
        $this->assertSame(2, CraftsInventoryColumnTypeEnum::CHECKBOX->value);
    }

    #[Test]
    public function select_case_is_three(): void
    {
        $this->assertSame(3, CraftsInventoryColumnTypeEnum::SELECT->value);
    }

    #[Test]
    public function number_case_is_four(): void
    {
        $this->assertSame(4, CraftsInventoryColumnTypeEnum::NUMBER->value);
    }

    #[Test]
    public function upload_case_is_five(): void
    {
        $this->assertSame(5, CraftsInventoryColumnTypeEnum::UPLOAD->value);
    }

    #[Test]
    public function last_edit_and_editor_case_is_ninety_nine(): void
    {
        $this->assertSame(99, CraftsInventoryColumnTypeEnum::LAST_EDIT_AND_EDITOR->value);
    }

    #[Test]
    public function it_has_seven_cases(): void
    {
        $this->assertCount(7, CraftsInventoryColumnTypeEnum::cases());
    }

    #[Test]
    public function try_from_returns_null_for_unknown(): void
    {
        $this->assertNull(CraftsInventoryColumnTypeEnum::tryFrom(42));
    }
}
