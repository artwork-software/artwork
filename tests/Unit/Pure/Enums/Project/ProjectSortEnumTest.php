<?php

namespace Tests\Unit\Pure\Enums\Project;

use Artwork\Modules\Project\Enum\ProjectSortEnum;
use PHPUnit\Framework\Attributes\Test;
use Tests\Unit\UnitTestCase;

final class ProjectSortEnumTest extends UnitTestCase
{
    #[Test]
    public function it_has_four_cases(): void
    {
        $this->assertCount(4, ProjectSortEnum::cases());
    }

    #[Test]
    public function alphabetically_ascending_maps_to_name(): void
    {
        $this->assertSame('name', ProjectSortEnum::ALPHABETICALLY_ASCENDING->mapToColumn());
    }

    #[Test]
    public function alphabetically_descending_maps_to_name(): void
    {
        $this->assertSame('name', ProjectSortEnum::ALPHABETICALLY_DESCENDING->mapToColumn());
    }

    #[Test]
    public function chronologically_ascending_maps_to_event_times(): void
    {
        $this->assertSame(['start_time', 'end_time'], ProjectSortEnum::CHRONOLOGICALLY_ASCENDING->mapToColumn());
    }

    #[Test]
    public function chronologically_descending_maps_to_event_times(): void
    {
        $this->assertSame(['start_time', 'end_time'], ProjectSortEnum::CHRONOLOGICALLY_DESCENDING->mapToColumn());
    }

    #[Test]
    public function ascending_directions_are_asc(): void
    {
        $this->assertSame('asc', ProjectSortEnum::ALPHABETICALLY_ASCENDING->mapToDirection());
        $this->assertSame('asc', ProjectSortEnum::CHRONOLOGICALLY_ASCENDING->mapToDirection());
    }

    #[Test]
    public function descending_directions_are_desc(): void
    {
        $this->assertSame('desc', ProjectSortEnum::ALPHABETICALLY_DESCENDING->mapToDirection());
        $this->assertSame('desc', ProjectSortEnum::CHRONOLOGICALLY_DESCENDING->mapToDirection());
    }

    #[Test]
    public function try_from_returns_null_for_unknown(): void
    {
        $this->assertNull(ProjectSortEnum::tryFrom('whatever'));
    }
}
