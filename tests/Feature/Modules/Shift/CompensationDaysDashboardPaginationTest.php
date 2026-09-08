<?php

namespace Tests\Feature\Modules\Shift;

use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Shift\Models\CompensationDayOff;
use Artwork\Modules\User\Models\User;
use Carbon\Carbon;
use Inertia\Testing\AssertableInertia;
use Maatwebsite\Excel\Facades\Excel;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Ersatzfrei-Dashboard: die drei Listen (offen/überfällig/gewährt) sind serverseitig paginiert (je 25,
 * eigene Seitenparameter), Filter bleiben; der Excel-Export nimmt weiterhin die gefilterte Gesamtmenge.
 */
final class CompensationDaysDashboardPaginationTest extends FeatureTestCase
{
    private function compensationDay(User $user, Carbon $deadline, bool $granted = false): CompensationDayOff
    {
        return CompensationDayOff::create([
            'user_id' => $user->id,
            'violation_id' => null,
            'value' => 1.0,
            'deadline' => $deadline->toDateString(),
            'reason' => 'Test',
            'granted_date' => $granted ? $deadline->copy()->subDay()->toDateString() : null,
            'granted_at' => $granted ? now() : null,
        ]);
    }

    #[Test]
    public function lists_are_paginated_independently_with_25_per_page(): void
    {
        $this->actingAsUserWith(PermissionEnum::SHIFT_PLANNER->value);
        $user = User::factory()->create();
        for ($i = 0; $i < 30; $i++) {
            $this->compensationDay($user, Carbon::now()->addDays(10 + $i));          // offen
        }
        for ($i = 0; $i < 27; $i++) {
            $this->compensationDay($user, Carbon::now()->subDays(1 + $i));           // überfällig (offen)
        }
        for ($i = 0; $i < 3; $i++) {
            $this->compensationDay($user, Carbon::now()->addDays(40 + $i), true);    // gewährt
        }

        $this->get(route('compensation-day-offs.dashboard'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('CompensationDays/Index')
                // "offen" enthält auch die überfälligen Einträge (57), Seite 1 = 25
                ->has('openCompensations.data', 25)
                ->where('openCompensations.total', 57)
                ->where('openCompensations.per_page', 25)
                ->has('overdueCompensations.data', 25)
                ->where('overdueCompensations.total', 27)
                ->has('grantedCompensations.data', 3)
                ->where('grantedCompensations.total', 3)
                ->where('stats.open', 57)
                ->where('stats.overdue', 27));

        // Eigene Seitenparameter: overdue_page blättert nur die Überfällig-Liste
        $this->get(route('compensation-day-offs.dashboard', ['overdue_page' => 2, 'open_page' => 3]))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('overdueCompensations.data', 2)
                ->where('overdueCompensations.current_page', 2)
                ->has('openCompensations.data', 7)
                ->where('openCompensations.current_page', 3)
                ->where('grantedCompensations.current_page', 1));
    }

    #[Test]
    public function filters_still_apply_and_status_filter_empties_the_other_lists(): void
    {
        $this->actingAsUserWith(PermissionEnum::SHIFT_PLANNER->value);
        $anna = User::factory()->create();
        $ben = User::factory()->create();
        $this->compensationDay($anna, Carbon::now()->addDays(10));
        $this->compensationDay($ben, Carbon::now()->addDays(12));
        $this->compensationDay($ben, Carbon::now()->addDays(40), true);

        $this->get(route('compensation-day-offs.dashboard', ['user_id' => $ben->id, 'status' => 'granted']))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('grantedCompensations.data', 1)
                ->where('grantedCompensations.data.0.user_id', $ben->id)
                ->has('openCompensations.data', 0)
                ->where('openCompensations.total', 0)
                ->has('overdueCompensations.data', 0)
                ->where('filters.user_id', $ben->id)
                ->where('filters.status', 'granted'));
    }

    #[Test]
    public function export_still_covers_the_whole_filtered_set_not_only_one_page(): void
    {
        Excel::fake();
        $this->actingAsUserWith(PermissionEnum::SHIFT_PLANNER->value);
        $user = User::factory()->create();
        for ($i = 0; $i < 30; $i++) {
            $this->compensationDay($user, Carbon::now()->addDays(10 + $i));
        }

        $this->get(route('compensation-day-offs.export', ['user_id' => $user->id, 'status' => 'open']))->assertOk();

        Excel::assertDownloaded(
            'Ersatzfreie_Tage_' . Carbon::today()->format('Y-m-d') . '.xlsx',
            fn (\Artwork\Modules\Shift\Exports\CompensationDaysExcelExport $export) => count($export->array()) === 30
        );
    }
}
