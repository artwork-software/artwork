<?php

namespace Tests\Feature\Modules\Shift;

use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Shift\Models\Shift;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Seite „Wochenstatus" (shifts.week-status): Rechte, Gewerks-Scoping, Props und Wochen-Deckel.
 */
final class ShiftWeekStatusPageTest extends FeatureTestCase
{
    #[Test]
    public function page_requires_view_shift_plan_permission(): void
    {
        $this->actingAsUserWith([PermissionEnum::SHIFT_PLANNER->value]);

        $this->get(route('shifts.week-status'))->assertForbidden();
    }

    #[Test]
    public function viewer_sees_default_props_with_eight_weeks_from_current_monday(): void
    {
        Craft::factory()->create(['assignable_by_all' => true]);
        $this->actingAsUserWith([PermissionEnum::VIEW_SHIFT_PLAN->value]);

        $expectedFrom = now()->startOfWeek()->toDateString();

        $this->get(route('shifts.week-status'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Shifts/WeekStatus')
                ->has('weeks', 8)
                ->where('filters.from', $expectedFrom)
                ->where('filters.weeks', 8)
                ->where('maxWeeks', 26)
                ->has('rows')
                ->has('summary')
                ->has('crafts', Craft::query()->where('assignable_by_all', true)->count())
                ->where('canCommit', false)
                ->where('canPlan', false)
                ->where('canApproveRequests', false)
                ->where('canSeeViolations', false)
                ->has('workflowEnabled'));
    }

    #[Test]
    public function planner_only_sees_assignable_or_own_crafts(): void
    {
        $user = $this->actingAsUserWith([
            PermissionEnum::VIEW_SHIFT_PLAN->value,
            PermissionEnum::SHIFT_PLANNER->value,
            PermissionEnum::CAN_COMMIT_SHIFTS->value,
        ]);

        $forAll = Craft::factory()->create(['assignable_by_all' => true, 'name' => 'Alle']);
        $own = Craft::factory()->create(['assignable_by_all' => false, 'name' => 'Eigenes']);
        $own->craftShiftPlaner()->attach($user->id);
        $foreign = Craft::factory()->create(['assignable_by_all' => false, 'name' => 'Fremd']);

        $this->get(route('shifts.week-status'))
            ->assertOk()
            ->assertInertia(function (AssertableInertia $page) use ($forAll, $own, $foreign): void {
                $craftIds = collect($page->toArray()['props']['crafts'])->pluck('id')->all();
                $this->assertContains($forAll->id, $craftIds);
                $this->assertContains($own->id, $craftIds);
                $this->assertNotContains($foreign->id, $craftIds);

                $selected = $page->toArray()['props']['filters']['craft_ids'];
                $this->assertContains($forAll->id, $selected);
                $this->assertContains($own->id, $selected);
                $this->assertNotContains($foreign->id, $selected);

                $page->where('canCommit', true)
                    ->where('canPlan', true)
                    ->has("rows.{$forAll->id}")
                    ->has("rows.{$own->id}")
                    ->missing("rows.{$foreign->id}");
            });
    }

    #[Test]
    public function requested_craft_ids_are_limited_to_visible_crafts(): void
    {
        $user = $this->actingAsUserWith([PermissionEnum::VIEW_SHIFT_PLAN->value]);

        $visible = Craft::factory()->create(['assignable_by_all' => false]);
        $visible->craftShiftPlaner()->attach($user->id);
        $alsoVisible = Craft::factory()->create(['assignable_by_all' => true]);
        $foreign = Craft::factory()->create(['assignable_by_all' => false]);

        $this->get(route('shifts.week-status', ['craft_ids' => [$visible->id, $foreign->id]]))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('filters.craft_ids', [$visible->id])
                ->has("rows.{$visible->id}")
                ->missing("rows.{$alsoVisible->id}")
                ->missing("rows.{$foreign->id}")
                // Die Gewerksliste (Filter) bleibt vollständig (alle sichtbaren, nicht nur die gewählten)
                ->has('crafts', Craft::query()->where('assignable_by_all', true)->count() + 1));
    }

    #[Test]
    public function admin_sees_all_crafts_and_has_all_actions(): void
    {
        Craft::factory()->create(['assignable_by_all' => false]);
        Craft::factory()->create(['assignable_by_all' => false]);
        $this->actingAsAdmin();

        $this->get(route('shifts.week-status'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('crafts', Craft::query()->count())
                ->where('canCommit', true)
                ->where('canPlan', true)
                ->where('canApproveRequests', true)
                ->where('canSeeViolations', true));
    }

    #[Test]
    public function from_is_normalized_to_monday_and_weeks_are_capped_at_26(): void
    {
        Craft::factory()->create(['assignable_by_all' => true]);
        $this->actingAsUserWith([PermissionEnum::VIEW_SHIFT_PLAN->value]);

        // 2026-10-07 ist ein Mittwoch → Montag 2026-10-05
        $this->get(route('shifts.week-status', ['from' => '2026-10-07', 'weeks' => 26]))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('filters.from', '2026-10-05')
                ->where('filters.to', '2027-04-04')
                ->where('filters.weeks', 26)
                ->has('weeks', 26)
                ->where('weeks.0.key', '2026-W41')
                ->where('weeks.0.monday_formatted', '05.10.2026'));

        $this->from(route('shifts.week-status'))
            ->get(route('shifts.week-status', ['from' => '2026-10-05', 'weeks' => 27]))
            ->assertRedirect(route('shifts.week-status'))
            ->assertSessionHasErrors(['weeks']);

        $this->from(route('shifts.week-status'))
            ->get(route('shifts.week-status', ['from' => '07.10.2026']))
            ->assertSessionHasErrors(['from']);
    }

    #[Test]
    public function cells_reflect_shift_status_of_the_visible_craft(): void
    {
        $craft = Craft::factory()->create(['assignable_by_all' => true, 'commit_request_deadline_days' => 2]);
        $this->actingAsUserWith([PermissionEnum::VIEW_SHIFT_PLAN->value]);

        Shift::factory()->create([
            'craft_id' => $craft->id,
            'start_date' => '2026-10-06',
            'end_date' => '2026-10-06',
            'start' => '10:00',
            'end' => '18:00',
            'is_committed' => true,
        ]);
        Shift::factory()->create([
            'craft_id' => $craft->id,
            'start_date' => '2026-10-14',
            'end_date' => '2026-10-14',
            'start' => '10:00',
            'end' => '18:00',
            'is_committed' => false,
        ]);

        $this->get(route('shifts.week-status', ['from' => '2026-10-05', 'weeks' => 4]))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where("rows.{$craft->id}.2026-W41.status", 'committed')
                ->where("rows.{$craft->id}.2026-W41.shifts_total", 1)
                ->where("rows.{$craft->id}.2026-W41.deadline_date", '2026-10-03')
                ->where("rows.{$craft->id}.2026-W42.status", 'open')
                ->where("rows.{$craft->id}.2026-W43.status", 'none')
                ->where('summary.2026-W41.crafts_committed', 1)
                ->where('summary.2026-W42.crafts_committed', 0)
                ->where('summary.2026-W42.crafts_with_shifts', 1)
                ->where('crafts', function ($crafts) use ($craft): bool {
                    $entry = collect($crafts)->firstWhere('id', $craft->id);

                    return $entry !== null && (int) $entry['commit_request_deadline_days'] === 2;
                }));
    }
}
