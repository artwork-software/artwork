<?php

namespace Tests\Unit\Pure\Enums\Crm;

use Artwork\Modules\Crm\Enums\CrmPropertyTypeEnum;
use PHPUnit\Framework\Attributes\Test;
use Tests\Unit\UnitTestCase;

final class CrmPropertyTypeEnumTest extends UnitTestCase
{
    #[Test]
    public function it_has_text_case(): void
    {
        $this->assertSame('text', CrmPropertyTypeEnum::TEXT->value);
    }

    #[Test]
    public function it_has_date_case(): void
    {
        $this->assertSame('date', CrmPropertyTypeEnum::DATE->value);
    }

    #[Test]
    public function it_has_number_case(): void
    {
        $this->assertSame('number', CrmPropertyTypeEnum::NUMBER->value);
    }

    #[Test]
    public function it_has_checkbox_case(): void
    {
        $this->assertSame('checkbox', CrmPropertyTypeEnum::CHECKBOX->value);
    }

    #[Test]
    public function it_has_select_case(): void
    {
        $this->assertSame('select', CrmPropertyTypeEnum::SELECT->value);
    }

    #[Test]
    public function it_has_link_case(): void
    {
        $this->assertSame('link', CrmPropertyTypeEnum::LINK->value);
    }

    #[Test]
    public function it_has_textarea_case(): void
    {
        $this->assertSame('textarea', CrmPropertyTypeEnum::TEXTAREA->value);
    }

    #[Test]
    public function it_has_upload_case(): void
    {
        $this->assertSame('upload', CrmPropertyTypeEnum::UPLOAD->value);
    }

    #[Test]
    public function it_has_eight_cases(): void
    {
        $this->assertCount(8, CrmPropertyTypeEnum::cases());
    }

    #[Test]
    public function try_from_returns_null_for_unknown(): void
    {
        $this->assertNull(CrmPropertyTypeEnum::tryFrom('unknown_type'));
    }
}
