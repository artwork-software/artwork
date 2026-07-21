<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\User\Enums\UserFilterTypes;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class ShareCalendarDateTest extends FeatureTestCase
{
    /**
     * Alle Filtertypen, die am geteilten Zeitraum teilnehmen (alles außer
     * PROJECT_SHIFT_FILTER, der dem Projektzeitraum folgt).
     */
    private const SHARED_FILTER_TYPES = [
        'calendar_filter',
        'calendar_daily_filter',
        'planning_filter',
        'planning_daily_filter',
        'shift_filter',
        'shift_daily_filter',
        'shift_list_view_filter',
    ];

    private function assertAllSharedFiltersHaveDates(User $user, string $start, string $end): void
    {
        foreach (self::SHARED_FILTER_TYPES as $filterType) {
            $filter = $user->userFilters()->where('filter_type', $filterType)->first();

            $this->assertNotNull($filter, "Filter {$filterType} fehlt");
            $this->assertSame($start, $filter->start_date?->format('Y-m-d') ?? $filter->start_date, $filterType);
            $this->assertSame($end, $filter->end_date?->format('Y-m-d') ?? $filter->end_date, $filterType);
        }

        // Inventar-Artikelplanung nimmt ebenfalls am geteilten Zeitraum teil
        $inventoryFilter = $user->inventoryArticlePlanFilter()->first();
        $this->assertNotNull($inventoryFilter, 'Inventar-Artikelplan-Filter fehlt');
        $this->assertSame($start, $inventoryFilter->start_date?->format('Y-m-d'), 'inventory_article_plan');
        $this->assertSame($end, $inventoryFilter->end_date?->format('Y-m-d'), 'inventory_article_plan');
    }

    #[Test]
    public function calendar_date_update_only_touches_own_filter_when_sharing_is_off(): void
    {
        $user = User::factory()->create(['share_calendar_date' => false]);
        $this->actingAs($user);

        $this->patch(route('update.user.calendar.filter.dates', $user), [
            'start_date' => '2026-08-03',
            'end_date' => '2026-08-16',
            'isPlanning' => false,
            'isDailyView' => false,
        ]);

        $calendarFilter = $user->userFilters()
            ->where('filter_type', UserFilterTypes::CALENDAR_FILTER->value)
            ->first();
        $this->assertNotNull($calendarFilter);

        $this->assertNull(
            $user->userFilters()->where('filter_type', UserFilterTypes::SHIFT_FILTER->value)
                ->whereDate('start_date', '2026-08-03')->first()
        );
    }

    #[Test]
    public function calendar_date_update_syncs_all_views_when_sharing_is_on(): void
    {
        $user = User::factory()->create(['share_calendar_date' => true]);
        $this->actingAs($user);

        $this->patch(route('update.user.calendar.filter.dates', $user), [
            'start_date' => '2026-08-03',
            'end_date' => '2026-08-16',
            'isPlanning' => false,
            'isDailyView' => false,
        ]);

        $this->assertAllSharedFiltersHaveDates($user, '2026-08-03', '2026-08-16');
    }

    #[Test]
    public function shift_plan_date_update_syncs_all_views_when_sharing_is_on(): void
    {
        $user = User::factory()->create(['share_calendar_date' => true]);
        $this->actingAs($user);

        $this->patch(route('update.user.shift.calendar.filter.dates', $user), [
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-07',
            'isDailyView' => false,
        ]);

        $this->assertAllSharedFiltersHaveDates($user, '2026-09-01', '2026-09-07');
    }

    #[Test]
    public function list_view_date_update_syncs_all_views_when_sharing_is_on(): void
    {
        $user = User::factory()->create(['share_calendar_date' => true]);
        $this->actingAs($user);

        $this->patch(route('update.user.shift-list-view.filter.dates', $user), [
            'start_date' => '2026-10-05',
            'end_date' => '2026-10-11',
        ]);

        $this->assertAllSharedFiltersHaveDates($user, '2026-10-05', '2026-10-11');
    }

    #[Test]
    public function article_plan_date_update_only_touches_own_filter_when_sharing_is_off(): void
    {
        $user = User::factory()->create(['share_calendar_date' => false]);
        $this->actingAs($user);

        $this->patch(route('update.user.inventory.article-plan.filters.update', $user), [
            'start_date' => '2026-08-03',
            'end_date' => '2026-08-16',
        ]);

        $inventoryFilter = $user->inventoryArticlePlanFilter()->first();
        $this->assertSame('2026-08-03', $inventoryFilter->start_date?->format('Y-m-d'));

        $this->assertNull(
            $user->userFilters()->where('filter_type', UserFilterTypes::CALENDAR_FILTER->value)
                ->whereDate('start_date', '2026-08-03')->first()
        );
    }

    #[Test]
    public function article_plan_date_update_syncs_all_views_when_sharing_is_on(): void
    {
        $user = User::factory()->create(['share_calendar_date' => true]);
        $this->actingAs($user);

        $this->patch(route('update.user.inventory.article-plan.filters.update', $user), [
            'start_date' => '2026-09-14',
            'end_date' => '2026-09-27',
        ]);

        $this->assertAllSharedFiltersHaveDates($user, '2026-09-14', '2026-09-27');
    }

    #[Test]
    public function enabling_from_article_plan_view_settings_seeds_from_inventory_filter(): void
    {
        $user = User::factory()->create(['share_calendar_date' => false]);
        $this->actingAs($user);

        $user->inventoryArticlePlanFilter()->updateOrCreate([], [
            'start_date' => '2026-10-12',
            'end_date' => '2026-10-25',
        ]);

        $this->patchJson(route('update.user.inventory.article-plan.view-settings.update', $user), [
            'share_calendar_date' => true,
            'only_planned' => false,
            'open_categories' => [],
            'open_subcategories' => [],
        ])->assertSuccessful();

        $this->assertTrue($user->fresh()->share_calendar_date);
        $this->assertAllSharedFiltersHaveDates($user, '2026-10-12', '2026-10-25');
    }

    #[Test]
    public function enabling_the_setting_seeds_all_views_from_the_current_view(): void
    {
        $user = User::factory()->create(['share_calendar_date' => false]);
        $this->actingAs($user);

        $user->userFilters()->updateOrCreate(
            ['filter_type' => UserFilterTypes::SHIFT_FILTER->value],
            ['start_date' => '2026-11-02', 'end_date' => '2026-11-15']
        );

        $this->patchJson(route('user.calendar_settings.update', $user), [
            'is_shift_plan' => true,
            'is_daily_view' => false,
            'share_calendar_date' => true,
        ])->assertSuccessful();

        $this->assertTrue($user->fresh()->share_calendar_date);
        $this->assertAllSharedFiltersHaveDates($user, '2026-11-02', '2026-11-15');
    }

    #[Test]
    public function disabling_the_setting_keeps_existing_filter_dates(): void
    {
        $user = User::factory()->create(['share_calendar_date' => true]);
        $this->actingAs($user);

        $user->userFilters()->updateOrCreate(
            ['filter_type' => UserFilterTypes::CALENDAR_FILTER->value],
            ['start_date' => '2026-12-01', 'end_date' => '2026-12-14']
        );

        $this->patchJson(route('user.calendar_settings.update', $user), [
            'is_shift_plan' => false,
            'is_daily_view' => false,
            'share_calendar_date' => false,
        ])->assertSuccessful();

        $user->refresh();
        $this->assertFalse($user->share_calendar_date);

        $calendarFilter = $user->userFilters()
            ->where('filter_type', UserFilterTypes::CALENDAR_FILTER->value)
            ->first();
        $this->assertSame('2026-12-01', $calendarFilter->start_date?->format('Y-m-d') ?? $calendarFilter->start_date);
    }

    #[Test]
    public function enabling_from_list_view_settings_seeds_from_list_view_filter(): void
    {
        $user = User::factory()->create(['share_calendar_date' => false]);
        $this->actingAs($user);

        $user->userFilters()->updateOrCreate(
            ['filter_type' => UserFilterTypes::SHIFT_LIST_VIEW_FILTER->value],
            ['start_date' => '2026-08-24', 'end_date' => '2026-08-30']
        );

        $this->patchJson(route('user.shift_list_view_settings.update', $user), [
            'share_calendar_date' => true,
        ])->assertSuccessful();

        $this->assertTrue($user->fresh()->share_calendar_date);
        $this->assertAllSharedFiltersHaveDates($user, '2026-08-24', '2026-08-30');
    }

    #[Test]
    public function switching_to_daily_view_does_not_shrink_the_shared_period(): void
    {
        $user = User::factory()->create(['share_calendar_date' => true]);
        $this->actingAs($user);

        foreach (self::SHARED_FILTER_TYPES as $filterType) {
            $user->userFilters()->updateOrCreate(
                ['filter_type' => $filterType],
                ['start_date' => '2026-08-03', 'end_date' => '2026-08-31']
            );
        }
        $user->inventoryArticlePlanFilter()->updateOrCreate([], [
            'start_date' => '2026-08-03',
            'end_date' => '2026-08-31',
        ]);

        $this->patch(route('user.update.daily_view', $user), [
            'daily_view' => true,
            'context' => 'calendar',
        ]);

        // Ohne Sharing würde der Seed die Tagesansicht auf start+7 kürzen —
        // mit geteiltem Zeitraum bleibt der gemeinsame Zeitraum unangetastet.
        $this->assertAllSharedFiltersHaveDates($user, '2026-08-03', '2026-08-31');
    }

    #[Test]
    public function user_cannot_change_another_users_shared_calendar_dates_or_view(): void
    {
        $attacker = User::factory()->create();
        $target = User::factory()->create(['share_calendar_date' => true]);
        $this->actingAs($attacker);

        $this->patch(route('update.user.calendar.filter.dates', $target), [
            'start_date' => '2026-08-03',
            'end_date' => '2026-08-16',
            'isPlanning' => false,
            'isDailyView' => false,
        ])->assertForbidden();

        $this->patch(route('user.update.daily_view', $target), [
            'daily_view' => true,
            'context' => 'calendar',
        ])->assertForbidden();
    }
}
