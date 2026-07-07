<?php

namespace Tests\Unit\Pure\Enums\User;

use Artwork\Modules\User\Enums\UserFilterTypes;
use PHPUnit\Framework\Attributes\Test;
use Tests\Unit\UnitTestCase;

final class UserFilterTypesTest extends UnitTestCase
{
    #[Test]
    public function it_has_calendar_filter_case(): void
    {
        $this->assertSame('calendar_filter', UserFilterTypes::CALENDAR_FILTER->value);
    }

    #[Test]
    public function it_has_shift_filter_case(): void
    {
        $this->assertSame('shift_filter', UserFilterTypes::SHIFT_FILTER->value);
    }

    #[Test]
    public function it_has_project_shift_filter_case(): void
    {
        $this->assertSame('project_shift_filter', UserFilterTypes::PROJECT_SHIFT_FILTER->value);
    }

    #[Test]
    public function it_has_planning_filter_case(): void
    {
        $this->assertSame('planning_filter', UserFilterTypes::PLANNING_FILTER->value);
    }

    #[Test]
    public function it_has_calendar_daily_filter_case(): void
    {
        $this->assertSame('calendar_daily_filter', UserFilterTypes::CALENDAR_DAILY_FILTER->value);
    }

    #[Test]
    public function it_has_shift_daily_filter_case(): void
    {
        $this->assertSame('shift_daily_filter', UserFilterTypes::SHIFT_DAILY_FILTER->value);
    }

    #[Test]
    public function it_has_planning_daily_filter_case(): void
    {
        $this->assertSame('planning_daily_filter', UserFilterTypes::PLANNING_DAILY_FILTER->value);
    }

    #[Test]
    public function it_has_shift_list_view_filter_case(): void
    {
        $this->assertSame('shift_list_view_filter', UserFilterTypes::SHIFT_LIST_VIEW_FILTER->value);
    }

    #[Test]
    public function it_has_eight_cases(): void
    {
        $this->assertCount(8, UserFilterTypes::cases());
    }

    #[Test]
    public function try_from_returns_null_for_unknown(): void
    {
        $this->assertNull(UserFilterTypes::tryFrom('something_filter'));
    }
}
