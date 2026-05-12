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
    public function it_has_three_cases(): void
    {
        $this->assertCount(3, Vacation::cases());
    }

    #[Test]
    public function try_from_returns_null_for_unknown(): void
    {
        $this->assertNull(Vacation::tryFrom('SICK'));
    }
}
