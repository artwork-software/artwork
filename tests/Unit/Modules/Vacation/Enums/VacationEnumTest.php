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
}
