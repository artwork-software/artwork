<?php

namespace Tests\Unit\Pure\Enums\Shift;

use Artwork\Modules\Shift\Enums\ShiftTabSort;
use PHPUnit\Framework\Attributes\Test;
use Tests\Unit\UnitTestCase;

final class ShiftTabSortTest extends UnitTestCase
{
    #[Test]
    public function it_has_room_name_asc_case(): void
    {
        $this->assertSame('ROOM_NAME_ASC', ShiftTabSort::ROOM_NAME_ASC->value);
    }

    #[Test]
    public function it_has_room_name_desc_case(): void
    {
        $this->assertSame('ROOM_NAME_DESC', ShiftTabSort::ROOM_NAME_DESC->value);
    }

    #[Test]
    public function it_has_two_cases(): void
    {
        $this->assertCount(2, ShiftTabSort::cases());
    }

    #[Test]
    public function try_from_returns_null_for_unknown(): void
    {
        $this->assertNull(ShiftTabSort::tryFrom('UNKNOWN_SORT'));
    }
}
