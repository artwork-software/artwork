<?php

namespace Tests\Unit\Pure\Enums\Notification;

use Artwork\Modules\Notification\Enums\NotificationFrequencyEnum;
use PHPUnit\Framework\Attributes\Test;
use Tests\Unit\UnitTestCase;

final class NotificationFrequencyEnumTest extends UnitTestCase
{
    #[Test]
    public function it_has_immediately_case(): void
    {
        $this->assertSame('Immediately', NotificationFrequencyEnum::IMMEDIATELY->value);
    }

    #[Test]
    public function it_has_daily_case(): void
    {
        $this->assertSame('daily', NotificationFrequencyEnum::DAILY->value);
    }

    #[Test]
    public function it_has_weekly_twice_case(): void
    {
        $this->assertSame('weekly_twice', NotificationFrequencyEnum::WEEKLY_TWICE->value);
    }

    #[Test]
    public function it_has_weekly_once_case(): void
    {
        $this->assertSame('weekly_once', NotificationFrequencyEnum::WEEKLY_ONCE->value);
    }

    #[Test]
    public function it_has_expected_total_cases(): void
    {
        $this->assertCount(4, NotificationFrequencyEnum::cases());
    }

    #[Test]
    public function immediately_title_is_correct(): void
    {
        $this->assertSame('Immediately', NotificationFrequencyEnum::IMMEDIATELY->title());
    }

    #[Test]
    public function daily_title_is_correct(): void
    {
        $this->assertSame('Daily', NotificationFrequencyEnum::DAILY->title());
    }

    #[Test]
    public function weekly_twice_title_is_correct(): void
    {
        $this->assertSame('Twice a week', NotificationFrequencyEnum::WEEKLY_TWICE->title());
    }

    #[Test]
    public function weekly_once_title_is_correct(): void
    {
        $this->assertSame('Once a week', NotificationFrequencyEnum::WEEKLY_ONCE->title());
    }

    #[Test]
    public function try_from_returns_null_for_unknown(): void
    {
        $this->assertNull(NotificationFrequencyEnum::tryFrom('yearly'));
    }
}
