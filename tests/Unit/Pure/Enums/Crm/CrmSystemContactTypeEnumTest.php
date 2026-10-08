<?php

namespace Tests\Unit\Pure\Enums\Crm;

use Artwork\Modules\Crm\Enums\CrmSystemContactTypeEnum;
use PHPUnit\Framework\Attributes\Test;
use Tests\Unit\UnitTestCase;

final class CrmSystemContactTypeEnumTest extends UnitTestCase
{
    #[Test]
    public function it_has_freelancer_case(): void
    {
        $this->assertSame('freelancer', CrmSystemContactTypeEnum::FREELANCER->value);
    }

    #[Test]
    public function it_has_service_provider_case(): void
    {
        $this->assertSame('service_provider', CrmSystemContactTypeEnum::SERVICE_PROVIDER->value);
    }

    #[Test]
    public function it_has_manufacturer_case(): void
    {
        $this->assertSame('manufacturer', CrmSystemContactTypeEnum::MANUFACTURER->value);
    }

    #[Test]
    public function it_has_accommodation_case(): void
    {
        $this->assertSame('accommodation', CrmSystemContactTypeEnum::ACCOMMODATION->value);
    }

    #[Test]
    public function it_has_artist_case(): void
    {
        $this->assertSame('artist', CrmSystemContactTypeEnum::ARTIST->value);
    }

    #[Test]
    public function it_has_user_case(): void
    {
        $this->assertSame('user', CrmSystemContactTypeEnum::USER->value);
    }

    #[Test]
    public function it_has_ticketing_case(): void
    {
        $this->assertSame('ticketing', CrmSystemContactTypeEnum::TICKETING->value);
    }

    #[Test]
    public function it_has_seven_cases(): void
    {
        $this->assertCount(7, CrmSystemContactTypeEnum::cases());
    }

    #[Test]
    public function only_types_kept_by_another_source_are_mirrored(): void
    {
        $this->assertTrue(CrmSystemContactTypeEnum::isMirrored('ticketing'));
        $this->assertTrue(CrmSystemContactTypeEnum::isMirrored('user'));
        $this->assertFalse(CrmSystemContactTypeEnum::isMirrored('artist'));
        $this->assertFalse(CrmSystemContactTypeEnum::isMirrored(null));
    }

    #[Test]
    public function try_from_returns_null_for_unknown(): void
    {
        $this->assertNull(CrmSystemContactTypeEnum::tryFrom('partner'));
    }
}
