<?php

namespace Tests\Unit\Pure\Enums\User;

use Artwork\Modules\User\Enums\UserSortEnum;
use PHPUnit\Framework\Attributes\Test;
use Tests\Unit\UnitTestCase;

final class UserSortEnumTest extends UnitTestCase
{
    #[Test]
    public function it_has_alphabetically_ascending_case(): void
    {
        $this->assertSame('ALPHABETICALLY_ASCENDING', UserSortEnum::ALPHABETICALLY_ASCENDING->value);
    }

    #[Test]
    public function it_has_alphabetically_descending_case(): void
    {
        $this->assertSame('ALPHABETICALLY_DESCENDING', UserSortEnum::ALPHABETICALLY_DESCENDING->value);
    }

    #[Test]
    public function it_has_chronologically_ascending_case(): void
    {
        $this->assertSame('CHRONOLOGICALLY_ASCENDING', UserSortEnum::CHRONOLOGICALLY_ASCENDING->value);
    }

    #[Test]
    public function it_has_chronologically_descending_case(): void
    {
        $this->assertSame('CHRONOLOGICALLY_DESCENDING', UserSortEnum::CHRONOLOGICALLY_DESCENDING->value);
    }

    #[Test]
    public function it_has_four_cases(): void
    {
        $this->assertCount(4, UserSortEnum::cases());
    }

    #[Test]
    public function alphabetically_ascending_maps_to_last_name_and_first_name(): void
    {
        $this->assertSame(['last_name', 'first_name'], UserSortEnum::ALPHABETICALLY_ASCENDING->mapToColumn());
    }

    #[Test]
    public function alphabetically_descending_maps_to_last_name_and_first_name(): void
    {
        $this->assertSame(['last_name', 'first_name'], UserSortEnum::ALPHABETICALLY_DESCENDING->mapToColumn());
    }

    #[Test]
    public function chronologically_ascending_maps_to_created_at(): void
    {
        $this->assertSame('created_at', UserSortEnum::CHRONOLOGICALLY_ASCENDING->mapToColumn());
    }

    #[Test]
    public function chronologically_descending_maps_to_created_at(): void
    {
        $this->assertSame('created_at', UserSortEnum::CHRONOLOGICALLY_DESCENDING->mapToColumn());
    }

    #[Test]
    public function alphabetically_ascending_direction_is_asc(): void
    {
        $this->assertSame('asc', UserSortEnum::ALPHABETICALLY_ASCENDING->mapToDirection());
    }

    #[Test]
    public function chronologically_ascending_direction_is_asc(): void
    {
        $this->assertSame('asc', UserSortEnum::CHRONOLOGICALLY_ASCENDING->mapToDirection());
    }

    #[Test]
    public function alphabetically_descending_direction_is_desc(): void
    {
        $this->assertSame('desc', UserSortEnum::ALPHABETICALLY_DESCENDING->mapToDirection());
    }

    #[Test]
    public function chronologically_descending_direction_is_desc(): void
    {
        $this->assertSame('desc', UserSortEnum::CHRONOLOGICALLY_DESCENDING->mapToDirection());
    }

    #[Test]
    public function try_from_returns_null_for_unknown(): void
    {
        $this->assertNull(UserSortEnum::tryFrom('RANDOM_SORT'));
    }
}
