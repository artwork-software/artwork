<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\User\Enums\UserFilterTypes;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Tagesansicht des Dienstplans: bis acht Kalendertage ohne Kappung. Längere Zeiträume werden auf acht Tage
 * gekürzt, und der Hinweis nennt diese Grenze statt der 31 Tage der Kalender-Tagesansicht.
 */
final class ShiftPlanDailyViewLimitTest extends FeatureTestCase
{
    #[Test]
    public function the_shift_plan_daily_view_shows_eight_days_without_warning(): void
    {
        $user = $this->actingAsDailyViewAdmin('2026-10-01', '2026-10-08');

        $response = $this->get(route('shifts.plan'));

        $response->assertOk();
        $this->assertSame('', $response->inertiaProps('calendarWarningText'));
        $this->assertSame(['2026-10-01', '2026-10-08'], $response->inertiaProps('dateValue'));
        $this->assertSame('2026-10-08', $this->storedEndDate($user));
    }

    #[Test]
    public function the_shift_plan_daily_view_caps_longer_periods_to_eight_days_and_says_so(): void
    {
        $user = $this->actingAsDailyViewAdmin('2026-10-01', '2026-10-31');

        $response = $this->get(route('shifts.plan'));

        $response->assertOk();
        $this->assertSame(
            __('calendar.shift_plan_daily_view_info'),
            $response->inertiaProps('calendarWarningText')
        );
        $this->assertStringContainsString('8', $response->inertiaProps('calendarWarningText'));
        $this->assertSame(['2026-10-01', '2026-10-08'], $response->inertiaProps('dateValue'));
        $this->assertSame('2026-10-08', $this->storedEndDate($user));
    }

    private function actingAsDailyViewAdmin(string $start, string $end): User
    {
        $user = $this->actingAsAdmin();
        $user->update(['shift_plan_daily_view' => true]);
        $user->userFilters()->updateOrCreate(
            ['filter_type' => UserFilterTypes::SHIFT_DAILY_FILTER->value],
            ['start_date' => $start, 'end_date' => $end]
        );

        return $user;
    }

    private function storedEndDate(User $user): ?string
    {
        return $user->userFilters()
            ->where('filter_type', UserFilterTypes::SHIFT_DAILY_FILTER->value)
            ->first()
            ?->end_date
            ?->format('Y-m-d');
    }
}
