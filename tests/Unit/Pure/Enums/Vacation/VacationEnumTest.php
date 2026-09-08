<?php

namespace Tests\Unit\Pure\Enums\Vacation;

use Artwork\Modules\Vacation\Enums\Vacation;
use PHPUnit\Framework\Attributes\Test;
use Tests\Unit\UnitTestCase;

final class VacationEnumTest extends UnitTestCase
{
    #[Test]
    public function it_has_off_work_case(): void
    {
        $this->assertSame('OFF_WORK', Vacation::OFF_WORK->value);
    }

    #[Test]
    public function it_has_not_available_case(): void
    {
        $this->assertSame('NOT_AVAILABLE', Vacation::NOT_AVAILABLE->value);
    }

    #[Test]
    public function it_has_available_case(): void
    {
        $this->assertSame('AVAILABLE', Vacation::AVAILABLE->value);
    }

    #[Test]
    public function it_has_four_cases(): void
    {
        // OFF_WORK, NOT_AVAILABLE, AVAILABLE, FREE_WORK (Frei war vorher nur ein Magic String)
        $this->assertCount(4, Vacation::cases());
        $this->assertSame('FREE_WORK', Vacation::FREE_WORK->value);
    }

    #[Test]
    public function try_from_returns_null_for_unknown(): void
    {
        $this->assertNull(Vacation::tryFrom('SICK'));
    }
}
