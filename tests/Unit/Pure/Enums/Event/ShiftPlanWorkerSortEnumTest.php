<?php

namespace Tests\Unit\Pure\Enums\Event;

use Artwork\Modules\Event\Enum\ShiftPlanWorkerSortEnum;
use PHPUnit\Framework\Attributes\Test;
use Tests\Unit\UnitTestCase;

final class ShiftPlanWorkerSortEnumTest extends UnitTestCase
{
    #[Test]
    public function it_has_intern_extern_ascending_case(): void
    {
        $this->assertSame(
            'INTERN_EXTERN_ASCENDING',
            ShiftPlanWorkerSortEnum::INTERN_EXTERN_ASCENDING->value
        );
    }

    #[Test]
    public function it_has_intern_extern_descending_case(): void
    {
        $this->assertSame(
            'INTERN_EXTERN_DESCENDING',
            ShiftPlanWorkerSortEnum::INTERN_EXTERN_DESCENDING->value
        );
    }

    #[Test]
    public function it_has_alphabetically_name_ascending_case(): void
    {
        $this->assertSame(
            'ALPHABETICALLY_NAME_ASCENDING',
            ShiftPlanWorkerSortEnum::ALPHABETICALLY_NAME_ASCENDING->value
        );
    }

    #[Test]
    public function it_has_alphabetically_name_descending_case(): void
    {
        $this->assertSame(
            'ALPHABETICALLY_NAME_DESCENDING',
            ShiftPlanWorkerSortEnum::ALPHABETICALLY_NAME_DESCENDING->value
        );
    }

    #[Test]
    public function it_has_four_cases(): void
    {
        $this->assertCount(4, ShiftPlanWorkerSortEnum::cases());
    }

    #[Test]
    public function try_from_returns_null_for_unknown(): void
    {
        $this->assertNull(ShiftPlanWorkerSortEnum::tryFrom('unknown'));
    }
}
