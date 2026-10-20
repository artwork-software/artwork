<?php

namespace Tests\Feature\Modules\Calendar;

use Artwork\Modules\User\Enums\UserFilterTypes;
use Artwork\Modules\User\Models\User;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Tagesansicht von Kalender und Planungskalender: bis 31 Kalendertage ohne Kappung. Das Ende liegt auf 23:59:59;
 * mit Carbon 3 (diffInDays als Bruchzahl) schlug die Grenze schon bei genau 31 Tagen an.
 */
final class CalendarDailyViewLimitTest extends FeatureTestCase
{
    #[Test]
    public function the_calendar_daily_view_shows_31_days_without_warning(): void
    {
        $this->actingAsDailyViewAdmin();

        $this->get(route('events', ['start_date' => '2026-10-01', 'end_date' => '2026-10-31']))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->where('calendarWarningText', ''));
    }

    #[Test]
    public function the_calendar_daily_view_caps_32_days_to_31(): void
    {
        $user = $this->actingAsDailyViewAdmin();

        $this->get(route('events', ['start_date' => '2026-10-01', 'end_date' => '2026-11-01']))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('calendarWarningText', __('calendar.daily_view_info')));

        $filter = $user->userFilters()->where('filter_type', UserFilterTypes::CALENDAR_DAILY_FILTER->value)->first();
        $this->assertSame('2026-10-31', $filter->end_date?->format('Y-m-d'));
    }

    #[Test]
    public function the_planning_calendar_daily_view_shows_31_days_without_warning(): void
    {
        $user = $this->actingAsDailyViewAdmin();
        $this->planningDailyFilter($user, '2026-10-01', '2026-10-31');

        $this->get(route('planning-event-calendar.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->where('calendarWarningText', ''));
    }

    #[Test]
    public function the_planning_calendar_daily_view_caps_32_days_to_31(): void
    {
        $user = $this->actingAsDailyViewAdmin();
        $this->planningDailyFilter($user, '2026-10-01', '2026-11-01');

        $this->get(route('planning-event-calendar.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('calendarWarningText', __('calendar.daily_view_info')));

        $filter = $user->userFilters()->where('filter_type', UserFilterTypes::PLANNING_DAILY_FILTER->value)->first();
        $this->assertSame('2026-10-31', $filter->end_date?->format('Y-m-d'));
    }

    private function actingAsDailyViewAdmin(): User
    {
        $user = $this->actingAsAdmin();
        $user->update(['calendar_daily_view' => true]);

        return $user;
    }

    private function planningDailyFilter(User $user, string $start, string $end): void
    {
        $user->userFilters()->updateOrCreate(
            ['filter_type' => UserFilterTypes::PLANNING_DAILY_FILTER->value],
            ['start_date' => $start, 'end_date' => $end]
        );
    }
}
