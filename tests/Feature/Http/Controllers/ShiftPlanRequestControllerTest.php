<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftPlanRequest;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class ShiftPlanRequestControllerTest extends FeatureTestCase
{
    #[Test]
    public function guest_cannot_view_review(): void
    {
        $this->get(route('shifts.approvals.review'))
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_view_review_index(): void
    {
        $this->actingAsAdmin();

        $this->get(route('shifts.approvals.review'))->assertOk();
    }

    #[Test]
    public function admin_can_view_my_requests_index(): void
    {
        $this->actingAsAdmin();

        $this->get(route('shifts.approvals.requests'))->assertOk();
    }

    #[Test]
    public function admin_can_view_changes(): void
    {
        $this->actingAsAdmin();

        $this->get(route('shifts.approvals.changes'))->assertOk();
    }

    #[Test]
    public function admin_can_view_past_requests(): void
    {
        $this->actingAsAdmin();
        $craft = Craft::factory()->create();

        $response = $this->get(route('shifts.approvals.past-requests', [
            'craft' => $craft->id,
            'status' => 'approved',
            'offset' => 0,
        ]));

        $response->assertOk();
    }

    #[Test]
    public function guest_cannot_create_shift_plan_request(): void
    {
        $this->post(route('commit-shift-workflow-request.store'), [])
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_show_shift_plan_request(): void
    {
        $this->actingAsAdmin();
        $craft = Craft::factory()->create();
        $request = ShiftPlanRequest::factory()->create([
            'craft_id' => $craft->id,
        ]);

        $response = $this->get(route('shift-plan-requests.show', $request));

        $response->assertOk();
    }

    #[Test]
    public function show_contains_navigation_between_requests_of_same_craft(): void
    {
        $this->actingAsAdmin();
        $craft = Craft::factory()->create();
        $kw28 = ShiftPlanRequest::factory()->create(['craft_id' => $craft->id, 'week_number' => 28, 'year' => 2026]);
        $kw29 = ShiftPlanRequest::factory()->create(['craft_id' => $craft->id, 'week_number' => 29, 'year' => 2026]);
        $kw30 = ShiftPlanRequest::factory()->create(['craft_id' => $craft->id, 'week_number' => 30, 'year' => 2026]);
        // Anfrage eines anderen Gewerks darf in der Navigation nicht auftauchen
        ShiftPlanRequest::factory()->create(['week_number' => 29, 'year' => 2026]);

        $this->get(route('shift-plan-requests.show', $kw29))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('ShiftPlanRequests/Show')
                ->where('navigation.previous.id', $kw28->id)
                ->where('navigation.next.id', $kw30->id));
    }

    #[Test]
    public function navigation_is_empty_at_the_edges(): void
    {
        $this->actingAsAdmin();
        $craft = Craft::factory()->create();
        $kw28 = ShiftPlanRequest::factory()->create(['craft_id' => $craft->id, 'week_number' => 28, 'year' => 2026]);
        $kw29 = ShiftPlanRequest::factory()->create(['craft_id' => $craft->id, 'week_number' => 29, 'year' => 2026]);

        $this->get(route('shift-plan-requests.show', $kw28))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('navigation.previous', null)
                ->where('navigation.next.id', $kw29->id));

        $this->get(route('shift-plan-requests.show', $kw29))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('navigation.previous.id', $kw28->id)
                ->where('navigation.next', null));
    }

    #[Test]
    public function show_shifts_include_room_name(): void
    {
        $this->actingAsAdmin();
        $craft = Craft::factory()->create();
        $room = Room::factory()->create(['name' => 'Halle K1']);
        $request = ShiftPlanRequest::factory()->create([
            'craft_id' => $craft->id,
            'week_number' => 28,
            'year' => 2026,
        ]);
        $shift = Shift::factory()->create([
            'craft_id' => $craft->id,
            'room_id' => $room->id,
            'start_date' => '2026-07-07',
            'end_date' => '2026-07-07',
        ]);
        $request->requestedShifts()->attach($shift->id, ['snapshot' => json_encode([])]);

        $this->get(route('shift-plan-requests.show', $request))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('shifts.0.room_name', 'Halle K1'));
    }

    #[Test]
    public function navigation_orders_across_year_boundary(): void
    {
        $this->actingAsAdmin();
        $craft = Craft::factory()->create();
        $kw52 = ShiftPlanRequest::factory()->create(['craft_id' => $craft->id, 'week_number' => 52, 'year' => 2026]);
        $kw1 = ShiftPlanRequest::factory()->create(['craft_id' => $craft->id, 'week_number' => 1, 'year' => 2027]);

        $this->get(route('shift-plan-requests.show', $kw52))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('navigation.next.id', $kw1->id));
    }

    // --- Prüfansicht: Verstoß-Payload für das ViolationEditModal ------------------------------

    private function violationInCurrentWeekFor(\Artwork\Modules\User\Models\User $user): array
    {
        $date = \Carbon\Carbon::now()->startOfWeek(\Carbon\Carbon::MONDAY)->addDays(2);
        $rule = \Artwork\Modules\Shift\Models\ShiftRule::factory()->create([
            'trigger_type' => 'maxWorkingHoursOnDay',
            'individual_number_value' => 8.0,
            'default_compensation_days' => 1.0,
            'default_compensation_deadline_days' => 30,
        ]);
        $violation = \Artwork\Modules\Shift\Models\ShiftRuleViolation::factory()->create([
            'shift_rule_id' => $rule->id,
            'shift_id' => null,
            'user_id' => $user->id,
            'violation_date' => $date->toDateString(),
            'violation_data' => ['planned_hours' => 9.5, 'max_allowed' => 8.0],
            'severity' => 'warning',
            'status' => 'active',
        ]);

        return [$date, $rule, $violation];
    }

    #[Test]
    public function show_provides_the_full_violation_payload_for_the_edit_modal(): void
    {
        $this->actingAsAdmin();
        $craft = Craft::factory()->create();
        $worker = \Artwork\Modules\User\Models\User::factory()->create(['can_work_shifts' => true]);
        $craft->users()->attach($worker->id);
        [$date, $rule] = $this->violationInCurrentWeekFor($worker);
        $request = ShiftPlanRequest::factory()->create([
            'craft_id' => $craft->id,
            'week_number' => $date->isoWeek(),
            'year' => $date->isoWeekYear(),
        ]);

        $prefix = "shiftRuleViolations.{$worker->id}.{$date->toDateString()}.0";

        $this->get(route('shift-plan-requests.show', $request))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('ShiftPlanRequests/Show')
                ->where('canEditViolations', true)
                ->where("{$prefix}.violation_data.planned_hours", 9.5)
                ->where("{$prefix}.severity", 'warning')
                ->where("{$prefix}.status", 'active')
                ->has("{$prefix}.compensation_days")
                ->has("{$prefix}.compensation_deadline")
                ->has("{$prefix}.compensation_reason")
                ->has("{$prefix}.resolved_by")
                ->has("{$prefix}.resolved_at")
                ->has("{$prefix}.title")
                ->where("{$prefix}.shift_rule.trigger_type", 'maxWorkingHoursOnDay')
                ->where("{$prefix}.shift_rule.name", $rule->name)
                ->has("{$prefix}.shift_rule.description")
                ->has("{$prefix}.shift_rule.warning_color")
                ->has("{$prefix}.shift_rule.default_compensation_days")
                ->where("{$prefix}.shift_rule.default_compensation_deadline_days", 30));
    }

    #[Test]
    public function reviewers_without_rule_edit_permission_cannot_edit_violations_from_the_review(): void
    {
        // Workflow-Nutzer:in ohne Schichteinstellungs-Recht: sieht die Prüfansicht, aber nur Tooltip
        $reviewer = $this->actingAsUserWith(\Artwork\Modules\Permission\Enums\PermissionEnum::SHIFT_PLANNER->value);
        \Artwork\Modules\Shift\Models\ShiftCommitWorkflowUser::create(['user_id' => $reviewer->id]);
        $craft = Craft::factory()->create();
        $worker = \Artwork\Modules\User\Models\User::factory()->create(['can_work_shifts' => true]);
        $craft->users()->attach($worker->id);
        [$date, , $violation] = $this->violationInCurrentWeekFor($worker);
        $request = ShiftPlanRequest::factory()->create([
            'craft_id' => $craft->id,
            'week_number' => $date->isoWeek(),
            'year' => $date->isoWeekYear(),
        ]);

        $this->get(route('shift-plan-requests.show', $request))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->where('canEditViolations', false));

        // Ohne Planungsrecht: Bearbeiten-Endpunkt liefert 403
        $this->actingAsUserWith([]);
        $this->put(route('shift-rule-violations.process', ['violation' => $violation->id]), [
            'compensation_days' => 1.0,
            'compensation_deadline' => \Carbon\Carbon::now()->addDays(20)->toDateString(),
        ])->assertForbidden();
    }
}
