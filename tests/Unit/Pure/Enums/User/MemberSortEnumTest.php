<?php

namespace Tests\Unit\Pure\Enums\User;

use Artwork\Modules\User\Enums\MemberSortEnum;
use PHPUnit\Framework\Attributes\Test;
use Tests\Unit\UnitTestCase;

final class MemberSortEnumTest extends UnitTestCase
{
    #[Test]
    public function it_has_four_cases(): void
    {
        $this->assertCount(4, MemberSortEnum::cases());
    }

    #[Test]
    public function alphabetically_ascending_freelancer_maps_to_name_columns(): void
    {
        $this->assertSame(
            ['last_name', 'first_name'],
            MemberSortEnum::ALPHABETICALLY_ASCENDING->mapToColumn(1)
        );
    }

    #[Test]
    public function alphabetically_ascending_service_provider_maps_to_provider_name(): void
    {
        $this->assertSame('provider_name', MemberSortEnum::ALPHABETICALLY_ASCENDING->mapToColumn(2));
    }

    #[Test]
    public function alphabetically_descending_freelancer_maps_to_name_columns(): void
    {
        $this->assertSame(
            ['last_name', 'first_name'],
            MemberSortEnum::ALPHABETICALLY_DESCENDING->mapToColumn(1)
        );
    }

    #[Test]
    public function alphabetically_descending_service_provider_maps_to_provider_name(): void
    {
        $this->assertSame('provider_name', MemberSortEnum::ALPHABETICALLY_DESCENDING->mapToColumn(0));
    }

    #[Test]
    public function chronologically_columns_are_independent_of_user_type(): void
    {
        $this->assertSame('created_at', MemberSortEnum::CHRONOLOGICALLY_ASCENDING->mapToColumn(1));
        $this->assertSame('created_at', MemberSortEnum::CHRONOLOGICALLY_ASCENDING->mapToColumn(2));
        $this->assertSame('created_at', MemberSortEnum::CHRONOLOGICALLY_DESCENDING->mapToColumn(1));
    }

    #[Test]
    public function ascending_maps_to_asc(): void
    {
        $this->assertSame('asc', MemberSortEnum::ALPHABETICALLY_ASCENDING->mapToDirection());
        $this->assertSame('asc', MemberSortEnum::CHRONOLOGICALLY_ASCENDING->mapToDirection());
    }

    #[Test]
    public function descending_maps_to_desc(): void
    {
        $this->assertSame('desc', MemberSortEnum::ALPHABETICALLY_DESCENDING->mapToDirection());
        $this->assertSame('desc', MemberSortEnum::CHRONOLOGICALLY_DESCENDING->mapToDirection());
    }

    #[Test]
    public function try_from_returns_null_for_unknown(): void
    {
        $this->assertNull(MemberSortEnum::tryFrom('FOO'));
    }
}
