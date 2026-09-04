<?php

namespace Tests\Unit\Modules\Vacation\Enums;

use Artwork\Modules\Vacation\Enums\Vacation;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class VacationEnumTest extends TestCase
{
    #[Test]
    public function free_work_is_a_known_case(): void
    {
        $this->assertSame(Vacation::FREE_WORK, Vacation::from('FREE_WORK'));
        $this->assertSame('FREE_WORK', Vacation::FREE_WORK->value);
        $this->assertNotNull(Vacation::tryFrom('FREE_WORK'));
    }

    #[Test]
    public function self_service_absence_values_are_vacation_and_not_available_only(): void
    {
        $this->assertSame(['OFF_WORK', 'NOT_AVAILABLE'], Vacation::selfServiceAbsenceValues());
        $this->assertNotContains('FREE_WORK', Vacation::selfServiceAbsenceValues());
        $this->assertNotContains('AVAILABLE', Vacation::selfServiceAbsenceValues());
    }
}
